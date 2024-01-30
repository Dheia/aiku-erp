import { useLayoutStore } from "@/Stores/layout"
import { useLocaleStore } from "@/Stores/locale"
import { router, usePage } from "@inertiajs/vue3"
import { loadLanguageAsync } from "laravel-vue-i18n"
import { watchEffect } from "vue"
import { useEchoGrpPersonal } from '@/Stores/echo-grp-personal.js'
import { useEchoGrpGeneral } from '@/Stores/echo-grp-general.js'
import axios from "axios"
import { useLiveUsers } from '@/Stores/active-users'
import { Image } from "@/types/Image"

interface AuthUser {
    avatar_thumbnail: Image
    email: string
    username: string
}

export const initialiseApp = () => {
    const layout = useLayoutStore()
    const locale = useLocaleStore()

    const storageLayout = JSON.parse(localStorage.getItem('layout') ?? '{}')  // Get layout from localStorage
    layout.organisationsState = storageLayout  // { 'awa' : { currentShop: 'bali', currentWarehouse: 'ed' }, ... }

    const echoPersonal = useEchoGrpPersonal()
    const echoGeneral = useEchoGrpGeneral()

    useLiveUsers().subscribe()  // Websockets: active users
    echoGeneral.subscribe(usePage().props.layout.group?.id)  // Websockets: notification

    if (usePage().props?.auth?.user) {
        echoPersonal.subscribe(usePage().props.auth.user.id)

        router.on('navigate', (event) => {
            layout.currentParams = route().params  // current params
            layout.currentRoute = route().current()  // current route

            const currentRouteSplit = layout.currentRoute.split('.')  // to handle grp with route grp.xxx.zzz with org with route grp.org.xxx.zzz
            layout.currentModule = currentRouteSplit[1] == 'org' ? layout.currentRoute.split('.')[2] : layout.currentRoute.split('.')[1]  // grp.org.xxx.yyy.zzz to xxx

            // Set current shop, current warehouse, current fulfilment
            layout.organisationsState = {
                ...layout.organisationsState,
                [layout.currentParams.organisation]: {
                    currentShop: route().params.shop ?? layout.organisationsState?.[layout.currentParams.organisation]?.currentShop,
                    currentWarehouse: route().params.warehouse ?? layout.organisationsState?.[layout.currentParams.organisation]?.currentWarehouse,
                    currentFulfilment: route().params.fulfilment ?? layout.organisationsState?.[layout.currentParams.organisation]?.currentFulfilment,
                    currentType: route().params.shop ? 'shop' : route().params.fulfilment ? 'fulfilment' : layout.organisationsState?.[layout.currentParams.organisation]?.currentType
                }
            }

            localStorage.setItem('layout', JSON.stringify({
                ...storageLayout,
                [layout.currentParams.organisation]: {
                    currentShop: layout.organisationsState?.[layout.currentParams.organisation]?.currentShop,
                    currentWarehouse: layout.organisationsState?.[layout.currentParams.organisation]?.currentWarehouse,
                    currentFulfilment: layout.organisationsState?.[layout.currentParams.organisation]?.currentFulfilment,
                    currentType: layout.organisationsState?.[layout.currentParams.organisation]?.currentType
                }
            }))

            // console.log('qq', usePage().props.auth.user)

            const dataActiveUser = {
                ...usePage().props.auth.user,
                name: null,
                last_active: new Date(),
                action: 'navigate',
                current_page: {
                    label: event.detail.page.props.title,
                    url: event.detail.page.url,
                    icon_left: usePage().props.live_users?.icon_left || null,
                    icon_right: usePage().props.live_users?.icon_right || null,
                },
            }

            // To avoid emit on logout
            if(dataActiveUser.id){
                // Set to self
                useLiveUsers().liveUsers[usePage().props.auth.user.id ] = dataActiveUser
                
                // Websockets: broadcast to others
                window.Echo.join(`grp.live.users`).whisper('otherIsNavigating', dataActiveUser)
            }

        })
    }

    if (usePage().props.localeData) {
        loadLanguageAsync(usePage().props.localeData.language.code)
    }

    watchEffect(() => {
        // Aiku

        // Set group
        if (usePage().props.layout?.group) {
            layout.group = usePage().props.layout.group
        }

        // Set Organisations (for Multiselect in Topbar)
        if (usePage().props.layout?.organisations) {
            layout.organisations = usePage().props.layout.organisations
        }

        // Set Navigation (for LeftSidebar)
        if (usePage().props.layout?.navigation) {
            layout.navigation = usePage().props.layout.navigation ?? null;
        }

        // Set data of Locale (Language)
        if (usePage().props.localeData) {
            locale.language = usePage().props.localeData.language
            locale.languageOptions = usePage().props.localeData.languageOptions
        }

        // Set data of User
        if (usePage().props.auth.user) {
            layout.user = usePage().props.auth.user
        }

        layout.systemName = "Aiku"
    })
}
