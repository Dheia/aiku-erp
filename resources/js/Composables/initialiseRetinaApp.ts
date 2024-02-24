/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Sun, 18 Feb 2024 06:30:10 Central Standard Time, Mexico City, Mexico
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

import { useLayoutStore } from "@/Stores/retinaLayout";
import { useLocaleStore } from "@/Stores/locale";
import { usePage } from "@inertiajs/vue3";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { watchEffect } from "vue";
import { useEchoRetinaPersonal } from "@/Stores/echo-retina-personal.js";
import { useEchoRetinaWebsite } from "@/Stores/echo-retina-website.js";
import { useEchoRetinaCustomer } from "@/Stores/echo-retina-customer.js";
import { useLiveUsers } from "@/Stores/active-users";


export const initialiseRetinaApp = () => {
    const layout = useLayoutStore();
    const locale = useLocaleStore();

    const echoPersonal = useEchoRetinaPersonal();
    const echoWebsite = useEchoRetinaWebsite();
    const echoCustomer = useEchoRetinaCustomer();
    const echoLiveUsers = useLiveUsers();

    layout.liveUsers = usePage().props.liveUsers || null;

    if (layout.liveUsers?.enabled) {
         echoLiveUsers.subscribe()  // Websockets: active users
    }

    if (usePage().props.auth.user) {
        echoCustomer.subscribe(usePage().props.auth.user.customer_id);
        // Echo: Personal
        echoPersonal.subscribe(usePage().props.auth.user.id);
    }


    // Echo: Website wide websocket
    echoWebsite.subscribe(usePage().props.iris.id);  // Websockets: notification

    if (usePage().props.localeData) {
        loadLanguageAsync(usePage().props.localeData.language.code);
    }

    watchEffect(() => {
        // Set data of Navigation
        if (usePage().props.layout) {
            layout.navigation = usePage().props.layout.navigation || null;
            // layout.secondaryNavigation = usePage().props.layout.secondaryNavigation || null
        }

        // Set data of Locale (Language)
        if (usePage().props.localeData) {
            locale.language = usePage().props.localeData.language;
            locale.languageOptions = usePage().props.localeData.languageOptions;
        }

        if (usePage().props.app) {
            layout.app = usePage().props.app;
        }
        layout.app.name = "retina";

        layout.webUser = usePage().props.auth?.webUser || null;

        layout.currentParams = route().params;
        layout.currentRoute = route().current();


        let moduleName = (layout.currentRoute || "").split(".");
        layout.currentModule = moduleName.length > 1 ? moduleName[1] : "";

        if (usePage().props.auth?.user?.avatar_thumbnail) {
            layout.avatar_thumbnail = usePage().props.auth.user.avatar_thumbnail;
        }

    });

    return layout;
};
