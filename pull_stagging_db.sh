ssh staging pg_dump -p 5433 -Fc -x -f "paso/aiku-staging.dump" aiku_staging
scp staging:paso/aiku-staging.dump devops/devel/snapshots/aiku-staging.dump
echo "loading ..";
pg_restore -x --no-owner -j 15  -U "${USER}" -c -d aiku  "devops/devel/snapshots/aiku-staging.dump"
echo "🕺 done aiku staging loaded"
