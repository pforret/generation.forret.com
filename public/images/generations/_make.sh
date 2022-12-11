#!/usr/bin/env bash

function make_photo(){
    name="$1"
    url="$2"
    image=$(echo "$1" | awk -F, '{ gsub(/[^A-Za-z0-9 ]+/,""); gsub(/[ ]+/,"_"); print tolower($0) ".jpg" }')
    if [[ ! -f "$image" ]] ; then
        splashmark -w 800 -c 200 -e dark,bw,grain -3 "GenerationZ" -i " " -k "$name" -z 100 url "$url" "$image"
    fi
}

make_photo "Baby Boomers Generation" "https://images.wsj.net/im-504443?width=1280&size=1"
make_photo "Generation Alpha" "https://cdn.mos.cms.futurecdn.net/MWLF9tpHEo6iNHNj4BW3TS.jpg"
make_photo "Generation X" "https://m.media-amazon.com/images/M/MV5BZjg2ODUwZTgtODRkMS00N2U1LTg2Y2EtNDVhMjRmMDNkNDk3XkEyXkFqcGdeQWFybm8@._V1_.jpg"
make_photo "Generation Z" "https://media-cldnry.s-nbcnews.com/image/upload/t_fit-1240w,f_auto,q_auto:best/newscms/2019_48/3121616/191126-charli-damelio-cs-247p.jpg"
make_photo "Greatest Generation" "https://www.history.com/.image/ar_16:9%2Cc_fill%2Ccs_srgb%2Cfl_progressive%2Cg_faces:center%2Cq_auto:good%2Cw_768/MTU3OTIzNTgwMTQyMjk5MDg2/eisenhower-knew-the-importance-of-d-day-for-an-allied-victorys-featured-photo.jpg"
make_photo "Interbellum Generation" "https://www.history.com/.image/ar_16:9%2Cc_fill%2Ccs_srgb%2Cfl_progressive%2Cq_auto:good%2Cw_1200/MTU3OTIzNjU0NDk4NzIzNDc0/the-pictures-that-defined-world-war-iis-featured-photo.jpg"
make_photo "Lost Generation" "https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/1908_Ford_Model_T.jpg/1200px-1908_Ford_Model_T.jpg"
make_photo "Millennials Generation" "https://cdn.pocket-lint.com/r/s/1201x/assets/images/150401-tv-feature-harry-potter-image1-vpdnsqfrou.jpg"
make_photo "Silent Generation" "http://www.classicmoviehub.com/blog/wp-content/uploads/2020/07/tony-curtis-jack-lemmon-marilyn-monroe-some-like-it-hot-1.png"

git add .


