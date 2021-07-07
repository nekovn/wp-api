<?php
add_action('rest_api_init', function () {
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
        'methods'  => 'GET',
        'callback' => 'related_posts_endpoint'
    ));


});


//get post related by categories
function related_posts_endpoint($request_data)
{
    $posts = get_posts(
        array(
            'post_type' => 'post',
            'category__in' => wp_get_post_categories($request_data['post_id']),
            'posts_per_page' => 5,
            'post__not_in' => array($request_data['post_id']),//your requested post id
        )
    );

    $newArray = [];
    foreach ($posts as $key=>$value){
        $newArray[$key]=array(
            'title'              => ['rendered'=> $value->post_title],
            'featured_media_url' => get_the_post_thumbnail_url($value->ID),
            'author_data'        => ['nickname'=> get_the_author_meta('nickname',$value->post_author)],
            'date'               => $value->post_date,
            'slug'               => $value->post_name

        );
    }
    $response = new WP_REST_Response(); // thành công thì trả về cho ng dùng
    $response -> set_data( $newArray );

    return $response;
}

//check password
function neko_check_user_password($value, $message = 'Mật khẩu')
{
    $password = (string)$value;

    if (empty($password)) {
        return new WP_Error(
            'rest_user_invalid_password_empty',
            __("$message không được rỗng."),
            array('status' => 400)
        );
    }


    $pattern = '#^(?=.*[a-z]).{3,18}$#';    // Php4567!
    if (!preg_match($pattern, $password)) {
        return new WP_Error(
            'rest_user_invalid_password_short',
            __("$message  phải dài từ 3 đến 18 ký tự."),
            array('status' => 400)
        );
    }
    $pattern = '#^(?=.*[A-Z]).{3,18}$#';    // Php4567!
    if (!preg_match($pattern, $password)) {
        return new WP_Error(
            'rest_user_invalid_password_flower',
            __("$message  phải có ít nhất 1 ký tự in hoa."),
            array('status' => 400)
        );
    }
    $pattern = '#^(?=.*\d).{3,18}$#';    // Php4567!
    if (!preg_match($pattern, $password)) {
        return new WP_Error(
            'rest_user_invalid_password_number',
            __("$message  phải có ít nhất 1 ký tự số."),
            array('status' => 400)
        );
    }

    if (false !== strpos($password, '\\')) {
        return new WP_Error(
            'rest_user_invalid_password_backslash',
            __("$message  không được có '\\'."),
            array('status' => 400)
        );
    }

    if (false !== strpos($password, ' ')) {
        return new WP_Error(
            'rest_user_invalid_password_space',
            __("$message  không được có khoảng trắng."),
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
    $new_password = neko_check_user_password($request->get_param('new_password'), 'Mật khẩu mới');
    $confirm_new_password = neko_check_user_password($request->get_param('confirm_new_password'),
        'Xác nhận mật khẩu mới');

    if (is_wp_error($password)) return $password;
    if (is_wp_error($new_password)) return $new_password;
    if (is_wp_error($confirm_new_password)) return $confirm_new_password;

    if ($password == $new_password) {
        return new WP_Error(
            'rest_user_invalid_new_password',
            __('Mật khẩu mới không được trùng với mật khẩu cũ.'),
            array('status' => 400)
        );
    }
    if ($new_password !== $confirm_new_password) {
        return new WP_Error(
            'rest_user_invalid_confirm_password',
            __('Xác nhận mật khẩu mới không khớp.'),
            array('status' => 400)
        );
    }

    $username = wp_get_current_user()->user_login;
    $user_check = wp_authenticate($username, $password); // wp_authenticate: hàm xem đăng nhập có thành công hay ko

    if (is_wp_error($user_check)) {
        return new WP_Error(
            'rest_user_invalid_password',
            __('Mật khẩu cũ không đúng.Vui lòng thử lại.'),
            array('status' => 400)
        );
    }
    $user_check->__set('user_pass', $confirm_new_password);//cập nhập lại gtri mới trong mật khẩu
//    user_pass: giá trị dc lưu trong bảng database
    $new_user = wp_update_user($user_check);
    if (is_wp_error($new_user)) {
        return new WP_Error(
            'rest_user_update_password',
            __('Có lỗi xảy ra trong quá trình xử lí .Vui lòng thử lại.'),
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

;

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
            __('Xác nhận mật khẩu mới không khớp.'),
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
            __('Tên đăng nhập không phù hợp.'),
            array('status' => 400)
        );
    }
    if (false !== strpos($username, ' ')) {
        return new WP_Error(
            'rest_user_invalid_empty',
            __('Tên đăng nhập không được có khoảng trắng.'),
            array('status' => 400)
        );
    }
    if (false !== strpos($username, '.')) {
        return new WP_Error(
            'rest_user_invalid_dots',
            __('Tên đăng nhập không được có dấu "."'),
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
            __('Bạn không thể đăng ký tài khoảng.'),
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
