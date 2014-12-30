#!/usr/bin/env bash

# first argument can be the domain name
DOMAIN="${1:-http://piero.local/GitHub_projects/mde-service/}"

INTERFACE='www/mde-api.php'
DEBUG='false'
#DEBUG='true'
MDE_OPTIONS='{}' # as JSON table
MDE_SOURCE='My *test* MDE **content** ... azerty `azerty()` azerty <http://google.com/> azerty.'

curl_opts='-i'
#curl_opts='-v'

echo "> test URL is : '${DOMAIN}/${INTERFACE}'"

echo
echo '### test with raw GET data:'
echo
curl "$curl_opts" \
    --get \
    --header "MDE-Editor: custom header for check" \
    --header "Time-Zone: Europe/Paris" \
    --data-urlencode "source=${MDE_SOURCE}" \
    --data-urlencode "options=${MDE_OPTIONS}" \
    --data-urlencode "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### test with raw POST data:'
echo
curl "$curl_opts" \
    --request POST \
    --header "MDE-Editor: custom header for check" \
    --data-urlencode "source=${MDE_SOURCE}" \
    --data-urlencode "options=${MDE_OPTIONS}" \
    --data-urlencode "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### test with json posted data:'
echo
echo "{ \
\"source\":     \"${MDE_SOURCE}\", \
\"options\":    \"${MDE_OPTIONS}\", \
\"debug\":      ${DEBUG} \
}" | curl "$curl_opts" \
    --request POST \
    --header "MDE-Editor: custom header for check" \
    --header "Content-Type: application/json" \
    --data @- \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### test with raw posted data and verb HEAD:'
echo
curl "$curl_opts" \
    --request HEAD \
    --header "MDE-Editor: custom header for check" \
    --data-urlencode "source=${MDE_SOURCE}" \
    --data-urlencode "options=${MDE_OPTIONS}" \
    --data-urlencode "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### test with empty "source" data:'
echo
curl "$curl_opts" \
    --get \
    --header "MDE-Editor: custom header for check" \
    --data-urlencode "source=''" \
    --data-urlencode "options=${MDE_OPTIONS}" \
    --data-urlencode "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### test with a bad request "verb":'
echo
curl "$curl_opts" \
    --request INPUT \
    --header "MDE-Editor: custom header for check" \
    --data-urlencode "source=''" \
    --data-urlencode "options=${MDE_OPTIONS}" \
    --data-urlencode "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo
