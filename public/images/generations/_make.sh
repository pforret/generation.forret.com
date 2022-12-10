#!/usr/bin/env bash

function make_photo(){
    name="$1"
    url="$2"
    image=$(echo "$1" | awk -F, '{ gsub(/[^A-Za-z0-9 ]+/,""); gsub(/[ ]+/,"_"); print tolower($0) ".jpg" }')
    if [[ ! -f "$image" ]] ; then
        splashmark -w 800 -c 400 -e dark,bw,grain -3 "GenerationZ" -i " " -k "$name" url "$url" "$image"
    fi
}

make_photo "Generation X" "https://www.cultura930.com.br/wp-content/uploads/2021/08/mtv-astronuta.jpg"
make_photo "Generation Z" "https://www.bark.us/wp-content/uploads/2019/11/How-TikTok-Is-Changing-What-Social-Media-Means-for-Generation-Z_Header.png"
make_photo "Baby Boomers" "https://images0.persgroep.net/rcs/gnaoF9oFAb755bHtY3RGprFAuYk/diocontent/162539005/_focus/0.48/0.59/_fill/1200/630/?appId=21791a8992982cd8da851550a453bd7f&quality=0.7"
git add .


