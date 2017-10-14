$(document).ready(function(){
	$('.me2').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
            $('.subMenu2').show();
            $('.subMenu3').hide();
            $('.subMenu4').hide();
            $('.subMenu5').hide();
            $('.subMenu6').hide();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
	});
	$('.me3').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
            $('.subMenu2').hide();
            $('.subMenu3').show();
            $('.subMenu4').hide();
            $('.subMenu5').hide();
            $('.subMenu6').hide();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
	});
    $('.me4').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
            $('.subMenu2').hide();
            $('.subMenu3').hide();
            $('.subMenu4').show();
            $('.subMenu5').hide();
            $('.subMenu6').hide();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
    });
    $('.me5').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
            $('.subMenu2').hide();
            $('.subMenu3').hide();
            $('.subMenu4').hide();
            $('.subMenu5').show();
            $('.subMenu6').hide();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
    });
    $('.me6').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
            $('.subMenu2').hide();
            $('.subMenu3').hide();
            $('.subMenu4').hide();
            $('.subMenu5').hide();
            $('.subMenu6').show();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
    });
    $('.me7').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
            $('.subMenu2').hide();
            $('.subMenu3').hide();
            $('.subMenu4').hide();
            $('.subMenu5').hide();
            $('.subMenu6').hide();
            $('.subMenu7').show();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
    });
    $('.me8').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();
            
        } else {
        $('.subMenu2').hide();
        $('.subMenu3').hide();
        $('.subMenu4').hide();
        $('.subMenu5').hide();
        $('.subMenu6').hide();
        $('.subMenu7').hide();
        $('.subMenu8').show();
        $('.subMenu9').hide();
        $('.subMenu11').hide();
        $(this).addClass("activeMenu");
        }
    });
    $('.me9').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();

        } else {
            $('.subMenu2').hide();
            $('.subMenu3').hide();
            $('.subMenu4').hide();
            $('.subMenu5').hide();
            $('.subMenu6').hide();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').show();
            $('.subMenu11').hide();
            $(this).addClass("activeMenu");
        }
    });
    $('.me11').click(function () {
        $('.activeMenu').removeClass('activeMenu');
        if ($(this).hasClass("activeMenu")) {
            $('.subContainer').hide();

        } else {
            $('.subMenu2').hide();
            $('.subMenu3').hide();
            $('.subMenu4').hide();
            $('.subMenu5').hide();
            $('.subMenu6').hide();
            $('.subMenu7').hide();
            $('.subMenu8').hide();
            $('.subMenu9').hide();
            $('.subMenu11').show();
            $(this).addClass("activeMenu");
        }
    });
    // ----------- MENU EVENTS --------------------------------------
var hasClass = $( "#topButton" ).hasClass( "menuShowButton" );

    $( ".menuHideButton" ).click(function() {
        if (hasClass == false){

            $( ".leftSide" ).animate({
                left: "-=220",
            }, 1000, function() {
                // Animation complete.
            });
            $( ".menuHideButton" ).animate({
                right: "-=30",
            }, 1000, function() {
                // Animation complete.
            });
            $( ".menuHideButton" ).addClass('menuShowButton');
            $( ".menuShowButton" ).empty();
            $( ".menuShowButton" ).append('>>');

            hasClass = $( "#topButton" ).hasClass( "menuShowButton" );
        } else {

            $( ".leftSide" ).animate({
                left: "+=220",
            }, 1000, function() {
                // Animation complete.
            });

            $( ".menuHideButton" ).animate({
                right: "+=30",
            }, 1000, function() {
                // Animation complete.
            });
            $( ".menuHideButton" ).removeClass('menuShowButton');
            $( ".menuHideButton" ).empty();
            $( ".menuHideButton" ).append('<<');

            hasClass = $( "#topButton" ).hasClass( "menuShowButton" );
        }

    });

    $( ".subContainer" ).on("mouseover", function(){
       // $(this).addClass("isOver");
    });

    $( ".subContainer" ).mouseleave(function(){

        // $(this).removeClass("isOver");
         $(".subContainer").hide();
    });


});