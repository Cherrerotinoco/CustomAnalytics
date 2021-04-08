{if !empty($codigo_seguimiento)} 

    {literal}
        <script defer>
        // Tracking Google Analytics
        var google_tag = document.createElement("script");
        google_tag.src = "https://www.googletagmanager.com/gtag/js?id={/literal}{$codigo_seguimiento}{literal}";

        document.getElementsByTagName('head')[0].appendChild(google_tag);


        window.dataLayer = window.dataLayer || [];
        function gtag(){
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', "{/literal}{$codigo_seguimiento}{literal}");


        // When DOM is Ready!
        document.addEventListener('DOMContentLoaded', () => {

            // Send event when click on Add to Cart
            jQuery(document).ajaxComplete(function(event, xhr, settings){
                
                // When a product is added to the car
                if ( settings.data && settings.data.includes('action=add-to-cart')) {
                        gtag('event', 'add_to_cart', {
                            "event_callback": console.log('Nuevo evento enviado: add_to_cart')
                            }
                        );
                }

                // When product is removed from the cart
                if ( settings.url && settings.url.includes('delete=') ) {
                        gtag('event', 'remove_from_cart', {
                            "event_callback": console.log('Nuevo evento enviado: remove_from_cart')
                            }
                        );
                }
            });
                
        });


        </script>
    {/literal}

{/if}
