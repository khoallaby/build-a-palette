(function($){


    wc_custom_palette = {
        _canvas : null,
        _container: null,
        _children: null,
        _child: null,
        _palette_image: null,
        _palette_image_container: null,
        coords: [
            { x: 28, y: 46 },
            { x: 128, y: 46 },
            { x: 228, y: 46 },
            { x: 328, y: 46 }

        ],


        init: function( container ) {

            this._container = $(container);
            this._children = this._container.find('.custom-palette-color');
            this._palette_image = $('img.custom-palette-image');
            this._palette_image_container = this._palette_image.parent();
            var i = 1;
            var _this = this;


            //this._container.tabs();
            this.setupCanvas();

            // iterate over everything 4x
            for (i = 1; i <= 4; i++) {
                this.setupImages( i );
            }


        },


        setupCanvas : function() {
            // todo: remove hardcoded background
            var image_url = 'http://magencydev.com/gabriel/media/2016/03/palette.jpg';

            // todo: remove canvas global
            this._canvas = canvas = new fabric.StaticCanvas( 'custom-palette-canvas', {
                backgroundImage: image_url
            });

        },


        setupImages : function( i ) {
            this._child = this._container.find('#custom-palette-color-' + i);
            var _this = this;

            this._child.find('.swatch-img').click( function(e) {
                e.preventDefault();
                var swatch_wrapper = $(this).parents('.swatch-wrapper');
                swatch_wrapper.parent().find('.swatch-wrapper').removeClass('selected');
                swatch_wrapper.addClass('selected');

                var swatch_data = swatch_wrapper.data();
                var color_data = $(this).parents('.custom-palette-color').data();

                $('form.cart input[name="cp_color_' + color_data.colorId  + '"]').val( swatch_data.variationId );
                _this.changeImage( color_data.colorId, swatch_data.thumbnail );
                // display color name in label
                $(this).parents('.custom-palette-color').find('.custom-palette-color-label').html( $(this).parent().attr('title') );


            });


        },



        changeImage : function( index, image_url ) {
            var new_index = wc_custom_palette.getImageIndexById('cp_color_' +  index );

            var positionScale   = 1,
                positionLeft    = 0,
                positionTop     = 0,
                positionHeight  = 128,
                positionWidth   = 128,
                positionAngle   = 0;

            if( new_index ) {
                var old_image = wc_custom_palette._canvas.item( new_index );
                positionScale = old_image.getScaleX();
                positionLeft = old_image.getLeft();
                positionTop = old_image.getTop();
                positionHeight = old_image.getHeight();
                positionWidth = old_image.getWidth();
                positionAngle = old_image.getAngle();
                old_image.remove();
            } else {
                positionLeft = wc_custom_palette.coords[(index-1)].x;
                positionTop = wc_custom_palette.coords[(index-1)].y;
            }


            fabric.Image.fromURL(image_url, function(obj) {

                obj.scale(positionScale).set({
                    id: 'cp_color_' + (index),

                    left: positionLeft,
                    top: positionTop,
                    height: positionHeight,
                    width: positionWidth,
                    clipTo: function (ctx) {
                        var radius = 46;
                        ctx.arc(0, 0, radius, 0, Math.PI * 2, true);
                    }
                });
                wc_custom_palette._canvas.add( obj );
                //wc_custom_palette._canvas.item(ni).scale(positionScale);
                //wc_custom_palette._canvas.renderAll();
                //wc_custom_palette._canvas.calcOffset();
            });


        },



        getImageIndexById : function(id) {
            for (var i = 0; i < wc_custom_palette._canvas.getObjects().length; ++i) {
                if (wc_custom_palette._canvas.item(i).id == id) {
                    return i;
                }
            }
            return false;
        }


    };


    $(document).ready(function() {
        var container_name = '.custom-palette-colors';
        $( container_name ).tabs();
        wc_custom_palette.init( container_name );
    });


})(jQuery);