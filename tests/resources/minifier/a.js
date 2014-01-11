/*!
 *
 * Script home.js
 *
 */

    var jCarousel	= $("#carousel");
    if(jCarousel.size() > 0){
        jCarousel.carousel({
            autoSlide: true,
            loop : true,
            pagination : true,
            dispItems : 1,
            btnsPosition: "inside",
            nextBtn: "",
            prevBtn: ""
        });
    }
