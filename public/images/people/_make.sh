#!/usr/bin/env bash

function make_photo(){
    name="$1"
    url="$2"
    image=$(echo "$1" | awk -F, '{ gsub(/[^A-Za-z0-9 ]+/,""); gsub(/[ ]+/,"-"); print tolower($0) ".jpg" }')
    if [[ ! -f "$image" ]] ; then
        splashmark -w 500 -c 500 -e dark,bw,grain -3 "GenerationZ" -i " " -k "$name" url "$url" "$image"
    fi
}

make_photo "AOC" "https://pbs.twimg.com/profile_images/923274881197895680/AbHcStkl_400x400.jpg"
make_photo "Charlize Theron" "https://i.pinimg.com/originals/03/df/a3/03dfa3a26731bee3b4c4ec34d06d519c.jpg"
make_photo "Christopher Nolan" "https://i.pinimg.com/originals/cc/68/4e/cc684eac6e588112d86e3a37dd7ea86f.jpg"
make_photo "Daniel Radcliffe" "https://static.cdn.turner.com/styles/img_square_768_x_768/s3/2020-01/Daniel-Radcliffe.jpg?itok=I4pNUrh4"
make_photo "Donald Trump" "https://cdn.vox-cdn.com/thumbor/ESoY5XGs3E1_v2kvoL7AmnL9d5o=/0x0:4032x5040/1400x1050/filters:focal(1737x1211:2381x1855):format(jpeg)/cdn.vox-cdn.com/uploads/chorus_image/image/52919091/PE_Color.0.jpg"
make_photo "Ed Sheeran" "https://media.vanityfair.com/photos/5f7dfd2eb13a7bb1006f7127/master/pass/ed.jpg"
make_photo "Eminem" "https://i.pinimg.com/564x/fc/1a/fc/fc1afc5d18263a5575dbd85a70b49ea9.jpg"
make_photo "Emma Watson" "https://assets.teenvogue.com/photos/561ac37da83003fb51eb83a8/master/pass/GettyImages-485378486.jpg"
make_photo "Emmanuel Macron" "https://upload.wikimedia.org/wikipedia/commons/d/d9/%D0%97%D1%83%D1%81%D1%82%D1%80%D1%96%D1%87_%D0%9F%D1%80%D0%B5%D0%B7%D0%B8%D0%B4%D0%B5%D0%BD%D1%82%D0%B0_%D0%A3%D0%BA%D1%80%D0%B0%D1%97%D0%BD%D0%B8_%D0%B7_%D0%BF%D1%80%D0%B5%D0%B7%D0%B8%D0%B4%D0%B5%D0%BD%D1%82%D0%B0%D0%BC%D0%B8_%D0%A4%D1%80%D0%B0%D0%BD%D1%86%D1%96%D1%97_%D1%82%D0%B0_%D0%A0%D1%83%D0%BC%D1%83%D0%BD%D1%96%D1%97%2C_%D0%B0_%D1%82%D0%B0%D0%BA%D0%BE%D0%B6_%D0%B3%D0%BE%D0%BB%D0%BE%D0%B2%D0%B0%D0%BC%D0%B8_%D1%83%D1%80%D1%8F%D0%B4%D1%96%D0%B2_%D0%9D%D1%96%D0%BC%D0%B5%D1%87%D1%87%D0%B8%D0%BD%D0%B8_%D1%82%D0%B0_%D0%86%D1%82%D0%B0%D0%BB%D1%96%D1%97_76_%28cropped%29.jpg"
make_photo "Greta Thunberg" "https://www.rollingstone.com/wp-content/uploads/2020/03/greta-featured-template.jpg"
make_photo "Jenna Ortega" "https://img.i-scmp.com/cdn-cgi/image/fit=contain,width=1098,format=auto/sites/default/files/styles/1200x800/public/d8/images/canvas/2022/11/24/18dd5eef-e679-4d3d-87f6-b38a964f8a58_19d5f4ae.jpg?itok=h5eXyQsb&v=1669284015"
make_photo "Jennifer Aniston" "https://s.yimg.com/ny/api/res/1.2/hoB21GYc6bxSQQXItc2Yzg--/YXBwaWQ9aGlnaGxhbmRlcjt3PTY0MDtoPTgyMw--/http://41.media.tumblr.com/7aa00629ef334b1eeaa48d88646a0e3d/tumblr_inline_nwxvgrIqWN1tu691f_1280.jpg"
make_photo "Jennifer Lawrence" "https://wallpaperstock.net/wallpapers/thumbs1/35872hd.jpg"
make_photo "Joe Biden" "https://people.com/thmb/b05eYtmP37--YBUKk_Pklad-DzQ=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc():focal(824x399:826x401)/Joe-Biden_02-dc79cc3192744094b49e65f528a34596.jpg"
make_photo "Jon Stewart" "https://media.npr.org/assets/img/2015/08/05/ap_681282264176_wide-6831f5aad5cfcfa8945267d357bba3dbebb7ccf7.jpg"
make_photo "Justin Trudeau" "https://www.rollingstone.com/wp-content/uploads/2017/07/r1293_cov_trudeauW.jpg"
make_photo "Kanye West" "https://imageio.forbes.com/specials-images/imageserve/5ed00f17d4a99d0006d2e738/0x0.jpg?format=jpg&crop=4666,4663,x154,y651,safe&height=416&width=416&fit=bounds"
make_photo "Kendall Jenner" "https://www.the-sun.com/wp-content/uploads/sites/6/2022/04/unnamed-1-36.jpg"
make_photo "Kim Kardashian" "https://www.highsnobiety.com/static-assets/thumbor/CDKs0ofABj08agHpRtmmVT0Guc8=/1200x800/www.highsnobiety.com/static-assets/wp-content/uploads/2022/09/26232523/kim-kardashian-dolce-gabbana-collection-0-e1669637381582.jpg"
make_photo "Kristen Stewart" "https://i.pinimg.com/originals/3d/87/fe/3d87fe3f7f2c3e96b0443aa47755f6b6.jpg"
make_photo "Kylie Minogue" "https://www.billboard.com/wp-content/uploads/media/kylie-minogue-press-2014-650-430.jpg"
make_photo "Louis C.K." "https://img.gva.be/7grR4v4jd0OO8AoAMht8NbM85M8=/fit-in/960x640/https%3A%2F%2Fstatic.gva.be%2FAssets%2FImages_Upload%2F2022%2F02%2F12%2F0538b5fd-fe8c-4478-92fc-64c5d0341edb.jpg"
make_photo "Margot Robbie" "https://media.cnn.com/api/v1/images/stellar/prod/221114132804-01-margot-robbie-file.jpg?c=original"
make_photo "Michael Jackson" "https://imagez.tmz.com/image/a9/4by3/2022/07/06/a958c51fbf7447b8876cca7a2e995352_md.jpg"
make_photo "Nicole Kidman" "https://the-talks.com/wp-content/uploads/2019/04/Nicole-Kidman-01.jpg"
make_photo "Penelope Cruz" "https://i.pinimg.com/originals/97/2e/47/972e47045f810bcc8f7e66769bff021b.jpg"
make_photo "Pete Davidson" "https://www.thewikifeed.com/wp-content/uploads/2021/08/pete-davidson-1.jpg"
make_photo "Quentin Tarantino" "https://www.biography.com/.image/t_share/MTkyNjgxNDU0MTkzMzUzNzU3/quentin-tarantino-gettyimages-1347472543.jpg"
make_photo "Rachel McAdams" "https://www.muscleandfitness.com/wp-content/uploads/2016/11/rachel_mcadams_main4.jpg?w=1180&quality=86&strip=all"
make_photo "Robert Pattinson" "https://i.pinimg.com/originals/03/47/a2/0347a29a6e049b37fe5d7f562cb1009b.jpg"
make_photo "Ryan Gosling" "https://img2.goodfon.com/wallpaper/nbig/b/fd/rayan-gosling-ryan-gosling.jpg"
make_photo "Ryan Reynolds" "https://superstarsbio.com/wp-content/uploads/2019/01/Ryan-Reynolds-Wallpapers-HD-20.jpg"
make_photo "Salma Hayek" "https://img4.goodfon.com/wallpaper/nbig/e/8c/salma-khaiek-salma-hayek-aktrisa-briunetka-pricheska-vzgliad.jpg"
make_photo "Sydney Sweeney" "https://www.shefinds.com/files/2022/02/sydney-sweeney-2.jpg"
make_photo "Taylor Swift" "https://pbs.twimg.com/media/EKd-93FWoAELVgM.jpg"
make_photo "Timothee Chalamet" "https://www.glamour.pl/media/cache/default_medium/uploads/media/default/0006/02/filmy-z-timotheem-chalametem-ktore-trzeba-obejrzec-top-8-produkcji.jpeg"
make_photo "Tina Fey" "https://m.media-amazon.com/images/M/MV5BNTJiOWYxMzQtNWZmYS00ZmNmLWE1ODktMGM3ZTkyYmE1YTBkXkEyXkFqcGdeQXVyMTAwOTg0NzU5._V1_.jpg"
make_photo "Tom Cruise" "https://www.biography.com/.image/t_share/MTc5ODc1NTM4NjMyOTc2Mzcz/gettyimages-693134468.jpg"
make_photo "Tom Holland" "https://64.media.tumblr.com/95d1079312d77857cfb69015168947a5/4da8e9399728a7be-78/s400x600/24d61766ec209c125ed5fbc9816a99f70e6f66a9.pnj"
make_photo "Trevor Noah" "https://variety.com/wp-content/uploads/2022/04/Trevor-Noah-GRAMMY-Host-Photo-Cred-Michael-Schwartz-CBS-HI-RES-1.jpg"
make_photo "Vladimir Putin" "https://media.vanityfair.com/photos/5874192bee23284912086649/1:1/w_960,h_960,c_limit/vladimir-putin-evil.jpg"
make_photo "Volodymyr Zelensky" "https://www.uu.nl/sites/default/files/styles/image_770x510/public/GW_HUM_zelenskyVolodymyr_770x510.jpg?itok=6zbaNO8w"
make_photo "Will Smith" "https://i.pinimg.com/originals/67/f8/06/67f8069527ae689ee3ae05a52bed2ce7.jpg"
make_photo "Willow Smith" "https://pyxis.nymag.com/v1/imgs/184/cea/5b1ab97b989e4baf444e38bcde493a182a-30-willow-smith.rsquare.w700.jpg"
make_photo "Zendaya" "https://a-static.besthdwallpaper.com/zwart-wit-portret-van-zendaya-mooie-jonge-vrouw-actrice-zangeres-danseres-behang-1600x1200-95477_23.jpg"
make_photo "Zoe Kravitz" "https://img4.goodfon.com/wallpaper/nbig/c/e6/zoe-kravitz-aktrisa-portret.jpg"
make_photo "person" "https://eitrawmaterials.eu/wp-content/uploads/2016/09/person-icon.png"
make_photo "the Weeknd" "https://images0.persgroep.net/rcs/Xv-3Yvmt0LYYSQNgETRhSEK9ASc/diocontent/210187775/_fitwidth/694/?appId=21791a8992982cd8da851550a453bd7f&quality=0.8"
make_photo "Ronald Reagan" "https://www.history.com/.image/c_fit%2Ccs_srgb%2Cfl_progressive%2Cq_auto:good%2Cw_620/MTU3ODc5MDg2NDM2NjU2NDU3/reagan_flags.jpg"
make_photo "Adolf Hitler" "https://cdn.britannica.com/58/156058-131-22083D0A/Adolf-Hitler.jpg"
make_photo "Richard Nixon" "https://upload.wikimedia.org/wikipedia/commons/3/3e/Richard_Nixon_presidential_portrait_%28cropped%29.jpg"
make_photo "Ruhollah Khomeini" "https://upload.wikimedia.org/wikipedia/commons/8/84/Ruhollah_Khomeini_portrait_1.jpg"
make_photo "Gregory Peck" "https://m.media-amazon.com/images/I/61syg6ziclL.jpg"

git add .


