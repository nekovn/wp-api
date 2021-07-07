<?php
//custom role
add_action('rest_api_init',function (){
    $wpRoles = wp_roles();//gọi wp roles
    $capSubscriber = $wpRoles->get_role('subscriber');//lấy quyền của subscriber
    if(!$capSubscriber->capabilities['upload_files']){
//        nếu subscriber chưa có quyền upload_files
//        thì thêm cho nó quyền upload_files
        $wpRoles->add_cap('subscriber','upload_files');
    }
    if(!$capSubscriber->capabilities['publish_posts']){
        $wpRoles->add_cap('subscriber','publish_posts');
    }
});
add_action('rest_api_init', function () {
    // Get Image Thumbnail by featured_media id of post
    register_rest_field('post',
        'featured_media_url', // Vi tri nay tren tuy chon
        array(
            'get_callback' => 'get_rest_featured_media_url' // Ten ham
        )
    );
    // Get Image Thumbnail by featured_media id for page
    register_rest_field('page',
        'featured_media_url', // Vi tri nay tren tuy chon
        array(
            'get_callback' => 'get_rest_featured_media_url' // Ten ham
        )
    );
    register_rest_field(array('post','comment'),
        'author_data',
        array(
            'get_callback' => 'get_rest_author_post_data'
        )
    );
    register_rest_field('post',
        'comment_count', // total parent + total reply
        array(
            'get_callback' => 'get_rest_post_comment_count'
        )
    );

    register_rest_field('post',
        'view_count',
        array(
            'get_callback' => 'get_rest_post_view_count'
        )
    );
//    comment replay
    register_rest_field('comment',
        'comment_reply_count',
        array(
            'get_callback' => 'get_rest_comment_reply_count'
        )
    );
});
//count total comment
function get_rest_post_comment_count($post, $field_name, $request)
{
    $post_id = $post['id'];

    if ($post_id) {
        $comment_count = get_comment_count($post_id); // đếm số lượt comment
        $comment_count = $comment_count['total_comments'];
        return (int)$comment_count;
    }
    return 0;
}
function get_rest_featured_media_url($post, $field_name, $request)
{
    $post_id = $post['id'];

    if ($post_id) {
        $url = get_the_post_thumbnail_url($post_id);
        // $arrUrl = wp_get_attachment_image_src($media_id);
        return $url;
    }

    return '';
}
function get_rest_author_post_data($post, $field_name, $request)
{
    $author_id = $post['author'];
    $user_meta = get_userdata($author_id);
    if ($author_id) {
        return array(
            'nickname'    => get_the_author_meta('nickname', $author_id),
            'description' => get_the_author_meta('description', $author_id),
            'avatar'      => get_user_meta($author_id, 'simple_local_avatar')[0]['full'],
            'total_posts' => count_user_posts( $author_id ),
            'roles_name'  => $user_meta->roles[0]


        );
    }

    return array(
        'nickname' => '',
        'avatar' => '',
        'description' => '',
    );
}
function get_rest_post_view_count($post, $field_name, $request)
{
    $post_id = $post['id'];

    if (function_exists('pvc_get_post_views')) {
        $view_count = pvc_get_post_views($post_id);
        return $view_count;
    }

    return 0;
}
function get_rest_comment_reply_count($comment,$field_name,$request)
{
    $post_id = $comment['post'];
    $comment_parent_id = $comment['id'];

    if($comment['parent']===0){
        global $wpdb;
        $query = "SELECT COUNT(comment_ID) as reply_count FROM $wpdb->comments 
                  WHERE `comment_post_ID`=$post_id AND `comment_approved`=1 AND `comment_parent`=$comment_parent_id";
        $data  = $wpdb->get_row($query);
        return (int)$data->reply_count;
    }
    return 0;
}
add_filter('rest_endpoints', function ($routes) {
    if (!$routes['/wp/v2/posts'][0]['args']['orderby']['enum']) {
        return $routes;
    }

    array_push($routes['/wp/v2/posts'][0]['args']['orderby']['enum'], 'post_views');
    return $routes;
});
add_filter('rest_prepare_user',function ($response,$user,$request){
    $data    = $response->get_data();//lay du lieu cua response
    $user_id = $data['id'];

    if($user_id){
        $data['email']      = $user->data->user_email;
        $data['user_name']  = $user->data->user_login;
        $data['first_name'] = get_user_meta($user_id,'first_name')[0];
        $data['last_name']  = get_user_meta($user_id,'last_name')[0];
        $data['nickname']   = get_user_meta($user_id,'nickname')[0];
    }
    $response = rest_ensure_response($data);//chuyển dữ liệu mới về response
    return $response;
},10,3);

add_filter( 'bdpwr_code_email_text' , function( $text , $email , $code , $expiry ) {
  $text = "Đặt lại mật khẩu đã được yêu cầu cho email :  " . $email .". Code đặt lại mật khẩu của bạn:  " . $code . ". \n sẽ hết hạn vào lúc" . bdpwr_get_formatted_date( $expiry ) . ".";
   return $text;
}, 10 , 4 );

//FILTER TO ADD CUSTOM NAMESPACE FOR REST API for reset password
add_filter( 'bdpwr_route_namespace' , function( $route_namespace ) {
    return 'wp/v2';
}, 10 , 1 );


//set subject
add_filter( 'bdpwr_code_email_subject' , function( $subject ) {
    return 'Đặt lại mật khẩu của bạn';
}, 10 , 1 );
//set length code
add_filter( 'bdpwr_code_length' , function( $length ) {
    return 6;
}, 10 , 1 );

//add api register
require_once ABSPATH . 'wp-custom-api-users.php';
?>
