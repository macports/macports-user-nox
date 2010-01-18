#!/bin/sh

treedir=$1
shift

function portfile {
    echo ${treedir}/$(port file "$1" | grep -o "\\([^/]*/\\)\\{2\\}[^/]*\$")
}

for port in $(port echo $@); do
    file=$(portfile ${port})
    echo Bumping ${port} in ${file}
    echo Checking for an existing revision...
    revision=$(grep ^revision "${file}" | awk '{print $2}')
    if test -n "${revision}"; then
        echo Revision found: ${revision}
        new=$(expr ${revision} + 1)
        echo Incrementing to ${new}...
        sed -i .before-bump /^revision/s/${revision}/${new}/ "${file}"
    else
        echo Revision not found
        echo Inserting revision 1
        sed -i .before-bump "/^version/a\\
revision        1
" "${file}"
    fi
    diff -u ${file}.before-bump ${file}
    echo Done.
done
