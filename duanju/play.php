<?php
// 设置HTTP头，阻止记录来源信息
header('Referrer-Policy: no-referrer');

// 从 URL 参数中获取动态 video_id（支持 GET 请求传递）
$videoId = isset($_GET['video_id']) ? $_GET['video_id'] : '';

// 验证 video_id 是否有效（示例：仅允许数字和字母，可根据实际需求调整）
if (!preg_match('/^[0-9a-zA-Z_-]+$/', $videoId)) {
    http_response_code(400);
    echo "无效的 video_id";
    exit;
}

// 拼接动态 API 地址
$apiUrl = sprintf('https://api.xingzhige.com/API/playlet/?video_id=%s', urlencode($videoId));

// 设置请求头和用户代理
$headers = ['Content-type: application/json;charset=UTF-8'];
$userAgent = 'RedFruitShortDrama/6.7.1 (Android; 13; Xiaomi MI 11)';

// 初始化CURL
$ch = curl_init();

// 配置CURL选项
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 禁用SSL验证，生产环境建议配置证书
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// 设置超时选项
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 连接超时时间（秒）
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 执行超时时间（秒）

// 执行请求并获取响应
$response = curl_exec($ch);

// 检查CURL错误
if (curl_errno($ch)) {
    http_response_code(500);
    echo 'CURL请求错误: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// 获取HTTP响应码
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 关闭CURL资源
curl_close($ch);

// 验证HTTP响应码
if ($httpCode != 200) {
    http_response_code(500);
    echo "API请求失败，HTTP状态码: $httpCode";
    exit;
}

// 解析 JSON 数据
$data = json_decode($response, true);

// 检查数据有效性
if ($data && isset($data['data']['video']['url'])) {
    $videoUrl = $data['data']['video']['url'];
    
    // 正确的重定向方法
    header("Location: $videoUrl", true, 302);
    exit;
} else {
    // 处理错误情况
    http_response_code(404);
    echo "未找到对应的视频链接";
    // 输出API原始响应用于调试
    if (isset($data['msg'])) {
        echo "\nAPI错误信息: " . $data['msg'];
    }
}
?>