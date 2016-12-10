(function($){


    var wc_custom_palette = {
        _container: null,
        _children: null,
        _child: null,
        _palette_image: null,
        _palette_image_container: null,
        it: null,


        init: function(  ) {
            this._container = $('.custom-palette-colors');
            this._children = this._container.find('.custom-palette-color');
            this._palette_image = $('img.custom-palette-image');
            this._palette_image_container = this._palette_image.parent();
            this.it = 1;


            this._container.tabs();

            // iterate over everything 4x
            for (this.it = 1; this.it <= 4; this.it++) {
                this.addImageOverlay();
                this._child = this._container.find('#custom-palette-color-' + this.it);
                this._child.find('.swatch-img').each( this.imageClick );
            }

        },


        imageClick : function( index, element ) {
            var swatch_data =  $(this).parents('.swatch-wrapper').data();
            var color_data =  $(this).parents('.custom-palette-color').data();

            //console.log(swatch_data);
            //console.log(color_data);

            $(this).click( function(e){
                e.preventDefault();
                $('form.cart input[name="cp_color_' + color_data.colorId  + '"]').val( swatch_data.variationId );
                $('.images .custom-palette-swatch-' + color_data.colorId ).attr( 'src', swatch_data.thumbnail );
            });


        },



        addImageOverlay : function() {
            var new_image = '<img src="" class="custom-palette-swatch custom-palette-swatch-' + this.it + '" />';
            this._palette_image_container.append( new_image );
        }


    };


    $(document).ready(function() {
        //$( ".custom-palette-colors" ).tabs();



        wc_custom_palette.init();
    });


})(jQuery);