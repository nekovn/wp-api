<?php
add_action('rest_api_init', function () {
    // custom Api register
    register_rest_route('wp/v2', '/users/register', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'handle_route_users_register',
        'args' => array(
            'username' => array(
                'description' => __('Login name for the user.'),
                'type' => 'string',
                'required' => true,
            ),
            'password' => array(
                'description' => __('Password for the user (never included).'),
                'type' => 'string',
                'required' => true,
            ),
            'nickname' => array(
                'description' => __('The nickname for the user.'),
                'type' => 'string',
                'required' => false,
            ),
            'email' => array(
                'description' => __('The email address for the user.'),
                'type' => 'string',
                'format' => 'email',
                'required' => true,
            ),

        )
    ));
    //custom Api change password
    register_rest_route('wp/v2', '/users/password', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'handle_route_users_change_password',
        'args' => array(
            'password' => array(
                'description' => __('Password for the user.'),
                'type' => 'string',
                'required' => true,
            ),
            'new_password' => array(
                'description' => __('New password for the user.'),
                'type' => 'string',
                'required' => true,
            ),
            'confirm_new_password' => array(
                'description' => __('Confirm new password for the user.'),
                'type' => 'string',
                'required' => true,
            ),
        )

    ));

    //custom api reset password and set new password
    register_rest_route('wp/v2', '/users/reset-password', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'handle_route_users_reset_password',
        'args' => array(
            'new_password' => array(
                'description' => __('New password for the user.'),
                'type' => 'string',
                'required' => true,
            ),
            'confirm_new_password' => array(
                'description' => __('Confirm new password for the user.'),
                'type' => 'string',
                'required' => true,
            ),
        )

    ));

    //custom api get post related by categories
    register_rest_route('wp/v2', '/post/related/', array(
        'methods' => 'GET',
        'callback' => 'related_posts_endpoint'
    ));
    // custom Api update post
    register_rest_route('wp/v2', '/users/post', array(
        'methods' => 'POST',
        'callback' => 'handle_route_users_post',
    ));
    // custom Api user infor
    register_rest_route('wp/v2', '/users/inf', array(
        'methods' => 'GET',
        'callback' => 'handle_route_users_inf',
    ));
    // custom Api other articles
    register_rest_route('wp/v2', '/post/other', array(
        'methods' => 'GET',
        'callback' => 'handle_route_post_other',
    ));
    // custom Api information admin
    register_rest_route('wp/v2', '/admin/inf', array(
        'methods' => 'GET',
        'callback' => 'handle_route_admin_inf',
    ));
    // custom Api posts pending
    register_rest_route('wp/v2', '/posts/pending', array(
        'methods' => 'GET',
        'callback' => 'get_rest_author_post_pending',
    ));
    // custom Api posts preview
    register_rest_route('wp/v2', '/posts/message', array(
        'methods' => 'GET',
        'callback' => 'get_rest_author_post_draft',
    ));
});
//posts draft
function get_rest_author_post_draft($request)
{
    $author_id = $request->get_param('author');
    if ($author_id) {
        $args = array(
            'author'      => $author_id,
            'orderby'     => 'post_date',
            'order'       => 'DESC',
            'post_status' => 'draft'
        );

        $draft = get_posts($args);
        $newArray = [];
        foreach ($draft as $key => $value) {
            $newArray[$key] = array(
                'id'            =>  $value->ID,
                'title'         =>  ['rendered' => $value->post_title],
                'content'       =>  ['rendered' => $value->post_content],
                'post_date'     =>  $value->post_date,
            );
        }
        $response = new WP_REST_Response(array(
            'message' => $newArray
        ), 200);
        return $response;
    } else {
        $response = new WP_REST_Response(array(
            'message' => ''
        ), 200);
        return $response;
    }
}
//posts pending
function get_rest_author_post_pending($request)
{
    $author_id = $request->get_param('author');
    if ($author_id) {
        $args = array(
            'author' => $author_id,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'post_status' => 'pending'
        );

        $pending = get_posts($args);
        $newArray = [];
        foreach ($pending as $key => $value) {
            $newArray[$key] = array(
                'title'               => ['rendered' => $value->post_title],
                'content'             => ['rendered' => $value->post_content],
                'comment_count'       => $value->comment_count,
                'categories'          =>  get_the_category($value->ID)[0]->name,
                'view_count'          =>  pvc_get_post_views($value->ID),
                'featured_media_url'  => get_the_post_thumbnail_url($value->ID),
                'id'                  =>  $value->ID,
            );
        }
        $response = new WP_REST_Response(array(
            'pending' => $newArray
        ), 200);
        return $response;
    } else {
        $response = new WP_REST_Response(array(
            'pending' => ''
        ), 200);
        return $response;
    }
}

//get dmin information
function handle_route_admin_inf()
{
    $data = array(
        'facebook_link' => get_user_meta(1, 'facebook', true),
        'instagram_link' => get_user_meta(1, 'instagram', true),
        'twitter_link' => get_user_meta(1, 'twitter', true),
        'email' => get_user_meta(1, 'soundcloud', true),
        'address' => get_user_meta(1, 'wikipedia', true),
        'tell' => get_user_meta(1, 'tumblr', true),

    );
    $response = new WP_REST_Response(array(
        'result' => $data,
        'status' => 200
    ), 200);

    return $response;

}

//get other articles
function handle_route_post_other($request)
{
    $args_post = array(
        'author' => $request->get_param('author'),
        'orderby' => 'post_date',
        'order' => 'ASC',
        'posts_per_page' => 10
    );
    $current_user_posts = get_posts($args_post);
    $post_ids = [];
    for ($i = 0; $i < count($current_user_posts); $i++) {
        $post_ids[] .= $current_user_posts[$i]->ID;
    }
    $args_post_new = array(
        'exclude' => $post_ids,
        'orderby' => 'post_date',
        'order' => 'ASC',
        'posts_per_page' => 10
    );
    $new_user_posts = get_posts($args_post_new);
    $newArray = [];
    foreach ($new_user_posts as $key => $value) {
        $newArray[$key] = array(
            'title' => ['rendered' => $value->post_title],
            'featured_media_url' => get_the_post_thumbnail_url($value->ID),
            'author_data' => ['nickname' => get_the_author_meta('nickname', $value->post_author)],
            'date' => $value->post_date,
            'slug' => $value->post_name

        );
    }

    $response = new WP_REST_Response(array(
        'result' => $newArray,
        'status' => 200
    ), 200);
    return $response;
}

//get user information
function handle_route_users_inf($request)
{
    $user_info = get_userdata($request->get_param('user_id'));
    $args_cm = array(
        'user_id' => $request->get_param('user_id'),   // Use post_id, not post_ID
        'count' => true // Return only the count
    );
    $args = array(
        'author' => $request->get_param('user_id'),
        'orderby' => 'post_date',
        'order' => 'ASC',
        'post_status' => 'publish'
    );

    $posts = get_posts($args);
    $view = [];
    for ($i = 0; $i < count($posts); $i++) {
        if (function_exists('pvc_get_post_views')) {
            $view[] .= pvc_get_post_views($posts[$i]->ID);
        }
    }
    $maxView = (int)max($view);
    $countView = array_sum($view);
    $countPost = (int)count_user_posts($request->get_param('user_id'));
    $countCm = get_comments($args_cm);
    $money = ($countView * 500) + ($countPost * 5000) + ($countCm * 500);

    if (function_exists('pvc_get_post_views')) {
        $arr_most_viewed = pvc_get_most_viewed_posts();
        $most_viewed = $arr_most_viewed[0]->post_views;
        if ($maxView === $most_viewed) {
            $vip = 1;
        } else {
            $vip = 0;
        }
    }

    $data = array(
        'user_nicename' => $user_info->nickname,
        'roles' => $user_info->roles[0],
        'user_registered' => $user_info->user_registered,
        'description' => get_the_author_meta('description', $request->get_param('user_id')),
        'avatar' => get_user_meta($request->get_param('user_id'), 'simple_local_avatar')[0]['full'],
        'total_posts' => $countPost,
        'total_comments' => $countCm,
        'view' => $maxView,
        'money' => $money,
        'vip' => $vip
    );
    $response = new WP_REST_Response(array(
        'result' => $data,
        'status' => 200
    ), 200);

    return $response;

}

//get post related by categories
function related_posts_endpoint($request_data)
{
    $posts = get_posts(
        array(
            'post_type' => 'post',
            'category__in' => wp_get_post_categories($request_data['post_id']),
            'posts_per_page' => 15,
            'post__not_in' => array($request_data['post_id']),//your requested post id
        )
    );

    $newArray = [];
    foreach ($posts as $key => $value) {
        $newArray[$key] = array(
            'title' => ['rendered' => $value->post_title],
            'featured_media_url' => get_the_post_thumbnail_url($value->ID),
            'author_data' => ['nickname' => get_the_author_meta('nickname', $value->post_author)],
            'date' => $value->post_date,
            'slug' => $value->post_name

        );
    }
    $response = new WP_REST_Response(); // thành công thì trả về cho ng dùng
    $response->set_data($newArray);

    return $response;
}

//get custom Api update post
function handle_route_users_post($request)
{
    $my_post = array(
        'post_title' => wp_strip_all_tags($request->get_param('post_title')),
        'post_content' => $request->get_param('post_content'),
        'post_status' => 'publish',
        'post_name' => $request->get_param('post_name'),
        'post_author' => $request->get_param('post_author'),
        'post_category' => array($request->get_param('post_category'))
    );

// Insert the post into the database
    $post_id = wp_insert_post($my_post);
    if (!$post_id) {
        return new WP_Error(
            'rest_user_post',
            __('Đã có lỗi xảy ra .Vui lòng thử lại sau！'),
            array('status' => 400)
        );
    }

    Generate_Featured_Image($request->get_param('image_url'), $post_id, $request->get_param('post_author'));

    $response = new WP_REST_Response(); // thành công thì trả về cho ng dùng
    $response->set_data(
        array(
            'updated' => true,
        )
    );
    return $response;

}

//Featured_Image
function Generate_Featured_Image($image_url, $post_id, $post_author)
{
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if (wp_mkdir_p($upload_dir['path']))
        $file = $upload_dir['path'] . '/' . $filename;
    else
        $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_author' => $post_author,
        'post_status' => 'inherit',

    );
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    set_post_thumbnail($post_id, $attach_id);
}

//check password
function neko_check_user_password($value, $message = 'mật khẩu')
{
    $password = (string)$value;

    if (empty($password)) {
        return new WP_Error(
            'rest_user_invalid_password_empty',
            __("$message không được trống！"),
            array('status' => 400)
        );
    }


    $pattern = '#^(?=.*[a-z]).{3,18}$#';    // Php4567!
    if (!preg_match($pattern, $password)) {
        return new WP_Error(
            'rest_user_invalid_password_short',
            __("$message  phải dài từ 3->18 ký tự！"),
            array('status' => 400)
        );
    }
    $pattern = '#^(?=.*[A-Z]).{3,18}$#';    // Php4567!
    if (!preg_match($pattern, $password)) {
        return new WP_Error(
            'rest_user_invalid_password_flower',
            __("$message  phải tồn tại ít nhất 1 chữ cái in hoa！"),
            array('status' => 400)
        );
    }
    $pattern = '#^(?=.*\d).{3,18}$#';    // Php4567!
    if (!preg_match($pattern, $password)) {
        return new WP_Error(
            'rest_user_invalid_password_number',
            __("$message  phải tồn tại ít nhất 1 chữ số！"),
            array('status' => 400)
        );
    }

    if (false !== strpos($password, '\\')) {
        return new WP_Error(
            'rest_user_invalid_password_backslash',
            __("$message  '\\'không chính xác！"),
            array('status' => 400)
        );
    }

    if (false !== strpos($password, ' ')) {
        return new WP_Error(
            'rest_user_invalid_password_space',
            __("$message  không được có khoảng trắng！"),
            array('status' => 400)
        );
    }

    return $password;
}

;
//handle_route_users_change_password
function handle_route_users_change_password($request)
{
    if (!is_user_logged_in()) {
        return new WP_Error(
            'jwt_invalid',
            'Unauthorized',
            array(
                'status' => 403,
            )
        );
    }

    $password = $request->get_param('password');
    $new_password = neko_check_user_password($request->get_param('new_password'), 'mật khẩu mới');
    $confirm_new_password = neko_check_user_password($request->get_param('confirm_new_password'),
        'xác nhận mật khẩu mới');

    if (is_wp_error($password)) return $password;
    if (is_wp_error($new_password)) return $new_password;
    if (is_wp_error($confirm_new_password)) return $confirm_new_password;

    if ($password == $new_password) {
        return new WP_Error(
            'rest_user_invalid_new_password',
            __('Mật khẩu cũ không được giống mật khẩu mới'),
            array('status' => 400)
        );
    }
    if ($new_password !== $confirm_new_password) {
        return new WP_Error(
            'rest_user_invalid_confirm_password',
            __('Xác nhận Mật khẩu mới không chính xác！'),
            array('status' => 400)
        );
    }

    $username = wp_get_current_user()->user_login;
    $user_check = wp_authenticate($username, $password); // wp_authenticate: hàm xem đăng nhập có thành công hay ko

    if (is_wp_error($user_check)) {
        return new WP_Error(
            'rest_user_invalid_password',
            __('Mật khẩu cũ không chính xác .Vui lòng nhập lại mật khẩu cũ！'),
            array('status' => 400)
        );
    }
    $user_check->__set('user_pass', $confirm_new_password);//cập nhập lại gtri mới trong mật khẩu
//    user_pass: giá trị dc lưu trong bảng database
    $new_user = wp_update_user($user_check);
    if (is_wp_error($new_user)) {
        return new WP_Error(
            'rest_user_update_password',
            __('Đã có lỗi xảy ra .Vui lòng nhập lại sau！'),
            array('status' => 400)
        );
    }

    $response = new WP_REST_Response(); // thành công thì trả về cho ng dùng
    $response->set_data(
        array(
            'updated' => true,
        )
    );
    return $response;
}

//handle_route_users_reset_password
function handle_route_users_reset_password($request)
{
    if (is_user_logged_in()) {
        return new WP_Error(
            'jwt_invalid',
            'Unauthorized',
            array(
                'status' => 403,
            )
        );
    }
    $new_password = neko_check_user_password($request->get_param('new_password'), 'Mật khẩu mới');
    $confirm_new_password = neko_check_user_password($request->get_param('confirm_new_password'),
        'Xác nhận mật khẩu mới');

    if (is_wp_error($new_password)) return $new_password;
    if (is_wp_error($confirm_new_password)) return $confirm_new_password;

    if ($new_password !== $confirm_new_password) {
        return new WP_Error(
            'rest_user_invalid_confirm_password',
            __('Xác nhận mật khẩu mới không chính xác！'),
            array('status' => 400)
        );
    }

    $response = new WP_REST_Response(); // thành công thì trả về cho ng dùng
    $response->set_data(
        array(
            'updated' => true,
            'password' => $new_password,
        )
    );
    return $response;
}

;


//check username
function neko_check_username($value)
{
    $username = (string)$value;
    if (!validate_username($username)) {
        return new WP_Error(
            'rest_user_invalid_username',
            __('Tên đăng nhập không hợp lệ！'),
            array('status' => 400)
        );
    }
    if (false !== strpos($username, ' ')) {
        return new WP_Error(
            'rest_user_invalid_empty',
            __('Tên đăng nhập không được có khoảng trắng！'),
            array('status' => 400)
        );
    }
    if (false !== strpos($username, '.')) {
        return new WP_Error(
            'rest_user_invalid_dots',
            __('Tên đăng nhập không được có dấu「。」'),
            array('status' => 400)
        );
    }

    return $username;
}

;
//check nick name
function neko_check_nickname($value, $default_value)
{
    $nickname = (string)$value;
    if (empty($nickname)) {
        return $default_value;
    }
    return $nickname;
}

//Function callback
function handle_route_users_register($request)
{
//    nếu admin check vào nút Membership thì mới dky đc user
    $users_can_register = (boolean)get_option('users_can_register');
    if ($users_can_register === false) {
        return new WP_Error(
            'rest_user_cannot_register',
            __('Không thể đăng ký tài khoảng！'),
            array('status' => 400)
        );
    }

    $email = $request->get_param('email');
    $username = neko_check_username($request->get_param('username'));
    $password = neko_check_user_password($request->get_param('password'));
    $nickname = neko_check_nickname($request->get_param('nickname'), $username);

//    instanceof neu password này là error thì nó return về error luôn
    if ($password instanceof WP_Error) return $password;
    if ($username instanceof WP_Error) return $username;


//    wp_insert_user lưu user vào database nếu true trả về id , còn false trả về error
    $userIdResult = wp_insert_user(array(
        'user_email' => $email,
        'user_pass' => $password,
        'user_login' => $username,
        'nickname' => $nickname
    ));
    if ($userIdResult instanceof WP_Error) {
        return $userIdResult;
    }

    $response = new WP_REST_Response(array(
        'author' => $userIdResult,
        'status' => 201
    ), 201);

    return $response;
}
