<?php 
$vk_code = isset($_REQUEST['code']) ? sanitize_text_field($_REQUEST['code']) : ''; //phpcs:ignore
if ($vk_code !== '') {
	echo "<script type='text/javascript' id='authorize'>";
            echo "arm_vk_token();";
            echo "function arm_vk_token(){";
            echo "window.opener.document.getElementById('arm_vk_user_data').value = '".json_encode(array())."';";
            echo "window.close();";
            echo "window.opener.arm_VKAuthCallBack('".$vk_code."')"; //phpcs:ignore
            echo "}";
            echo "</script>";
	exit;
}
