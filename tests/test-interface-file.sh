#!/usr/bin/env bash


# first argument can be the domain name
DOMAIN="${1:-http://piero.local/GitHub_projects/mde-service/}"

INTERFACE='www/mde-api.php'
UPLOADED_FILE='tests/test-file-1.md'
UPLOADED_FILE2='tests/test-file-2.md'
DEBUG='false'
#DEBUG='true'
MDE_OPTIONS='{}' # as JSON table
MDE_SOURCE='My *test* MDE **content** ... azerty `azerty()` azerty <http://google.com/> azerty.'

curl_opts='-i'
#curl_opts='-v'

echo "> test URL is : '${DOMAIN}/${INTERFACE}'"
echo "> test uploaded file is : '${DOMAIN}/${UPLOADED_FILE}'"

echo
echo '### test with uploaded file and POST data:'
echo
curl "$curl_opts" \
    --form "source=@${UPLOADED_FILE}" \
    --form "source_type=file" \
    --form "options=${MDE_OPTIONS}" \
    --form "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### same test with no "source_type" data:'
echo
curl "$curl_opts" \
    --form "source=@${UPLOADED_FILE}" \
    --form "options=${MDE_OPTIONS}" \
    --form "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo

echo
echo '### test with 2 uploaded files and POST data:'
echo
curl "$curl_opts" \
    --form "source1=@${UPLOADED_FILE}" \
    --form "source2=@${UPLOADED_FILE2}" \
    --form "source_type=file" \
    --form "options=${MDE_OPTIONS}" \
    --form "debug=${DEBUG}" \
    "${DOMAIN}/${INTERFACE}" ;
echo
