<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'the_content', 'ev_kirchen_termine_add_share_icons_to_content', 20 );

function ev_kirchen_termine_add_share_icons_to_content( $content ) {

    // Check if we're inside the main loop in a single Post.

    global $post;
    if ($post->post_type == 'event') {

        // remove auto <p>
        remove_filter( 'the_content', 'wpautop' );

        if(get_option("ev_kirchen_termine_show_share_icons")) {
            $content = $content . ev_kirchen_termine_social_icons();
        }

    }

    return $content;

}


function ev_kirchen_termine_social_icons() {

    $post_link  = esc_url( get_the_permalink() );
    $post_title = get_the_title();

    $facebook_url = add_query_arg(
        array(
            'u' => $post_link,
        ),
        'https://www.facebook.com/sharer.php'
    );

    $twitter_url = add_query_arg(
        array(
            'url'  => $post_link,
            'text' => rawurlencode( html_entity_decode( wp_strip_all_tags( $post_title ), ENT_COMPAT, 'UTF-8' ) ),
        ),
        'http://twitter.com/share'
    );

    $email_title = str_replace( '&', '%26', $post_title );

    $email_url = add_query_arg(
        array(
            'subject' => wp_strip_all_tags( $email_title ),
            'body'    => $post_link,
        ),
        'mailto:'
    );

    $whatsapp_url = add_query_arg(
        array(
            'text' => urlencode($post_title ." \n". $post_link),
        ),
        'whatsapp://send'
    );

    $return = '<div class="col-md-12">
                <div class="entry-social evkite-entry-social">

                    <a target="_blank"
                        class="btn btn-just-icon btn-round" style="background-color: #25D366"
                        href="' . $whatsapp_url . '">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214l-3.741.982l.998-3.648l-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z" fill="currentColor"/></svg>
                    </a>

                    <a target="_blank"
                       class="btn btn-just-icon btn-round" style="background-color: #3b5998"
                       href="' . esc_url( $facebook_url ) . '">
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="20" height="17"><path fill="currentColor" d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"></path></svg>
                    </a>

                    <a target="_blank"
                       class="btn btn-just-icon btn-round" style="background-color: #55acee"
                       href="' . esc_url( $twitter_url ) . '">
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="17"><path fill="currentColor" d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"></path></svg>
                    </a>

                    <a class="btn btn-just-icon btn-round"
                       href="' . esc_url( $email_url ) . '">
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="17"><path fill="currentColor" d="M502.3 190.8c3.9-3.1 9.7-.2 9.7 4.7V400c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V195.6c0-5 5.7-7.8 9.7-4.7 22.4 17.4 52.1 39.5 154.1 113.6 21.1 15.4 56.7 47.8 92.2 47.6 35.7.3 72-32.8 92.3-47.6 102-74.1 131.6-96.3 154-113.7zM256 320c23.2.4 56.6-29.2 73.4-41.4 132.7-96.3 142.8-104.7 173.4-128.7 5.8-4.5 9.2-11.5 9.2-18.9v-19c0-26.5-21.5-48-48-48H48C21.5 64 0 85.5 0 112v19c0 7.4 3.4 14.3 9.2 18.9 30.6 23.9 40.7 32.4 173.4 128.7 16.8 12.2 50.2 41.8 73.4 41.4z"></path></svg>
                   </a>

                </div>
            </div>';

    $return = str_replace(array("\n","\r","\t"), "", $return);

    return $return;

}
