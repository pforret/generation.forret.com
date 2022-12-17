#!/usr/bin/env bash

function make_photo(){
    name="$1"
    years="born in $2"
    url="$3"
    image=$(echo "$1" | awk -F, '{ gsub(/[^A-Za-z0-9 ]+/,""); gsub(/[ ]+/,"_"); print tolower($0) ".jpg" }')
    if [[ ! -f "$image" ]] ; then
        splashmark -w 1000 -c 500 -e dark,bw,grain -3 "GenerationZ" -i "$name" -k "$years" url "$url" "$image"
    fi
}

make_photo "Baby Boomers Generation" "1946-1964" "https://images.wsj.net/im-504443?width=1280&size=1"
make_photo "Generation Alpha"        "2013-2024" "https://cdn.mos.cms.futurecdn.net/MWLF9tpHEo6iNHNj4BW3TS.jpg"
make_photo "Alpha Generation"        "2013-2024" "https://cdn.mos.cms.futurecdn.net/MWLF9tpHEo6iNHNj4BW3TS.jpg"
make_photo "Generation X"            "1965-1980" "https://m.media-amazon.com/images/M/MV5BZjg2ODUwZTgtODRkMS00N2U1LTg2Y2EtNDVhMjRmMDNkNDk3XkEyXkFqcGdeQWFybm8@._V1_.jpg"
make_photo "Generation Z"            "1997-2012" "https://media-cldnry.s-nbcnews.com/image/upload/t_fit-1240w,f_auto,q_auto:best/newscms/2019_48/3121616/191126-charli-damelio-cs-247p.jpg"
make_photo "Greatest Generation"     "1914-1924" "https://www.history.com/.image/ar_16:9%2Cc_fill%2Ccs_srgb%2Cfl_progressive%2Cg_faces:center%2Cq_auto:good%2Cw_768/MTU3OTIzNTgwMTQyMjk5MDg2/eisenhower-knew-the-importance-of-d-day-for-an-allied-victorys-featured-photo.jpg"
make_photo "Interbellum Generation"  "1901-1913" "https://d.newsweek.com/en/full/1314496/gone-wind-gable.jpg?w=1600&h=1200&q=88&f=afe078c7a19dc1b6373a9785fd94168a"
make_photo "Lost Generation"         "1883-1900" "https://ds.static.rtbf.be/article/image/1920xAuto/a/f/9/069e383da81880387ed27c394e55198e-1541761765.jpg"
make_photo "Millennials Generation"  "1981-1996" "https://cdn.pocket-lint.com/r/s/1201x/assets/images/150401-tv-feature-harry-potter-image1-vpdnsqfrou.jpg"
make_photo "Silent Generation"       "1925-1945" "http://www.classicmoviehub.com/blog/wp-content/uploads/2020/07/tony-curtis-jack-lemmon-marilyn-monroe-some-like-it-hot-1.png"

git add .


