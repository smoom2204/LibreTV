<?php
// 安全获取book_id，过滤非法字符
$book_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['book_id'] ?? '');
if (empty($book_id)) {
    die('缺少必要参数book_id');
}

// 构建API URL
$apiUrl = "https://api.xingzhige.com/API/playlet/?book_id=" . urlencode($book_id);

// 初始化cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 开发环境可关闭，生产环境需验证证书
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 完整错误处理
if ($response === false) {
    die('cURL错误：' . curl_error($ch));
} elseif ($httpCode !== 200) {
    die('API请求失败，状态码：' . $httpCode);
}

// 解析JSON数据
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('JSON解析错误：' . json_last_error_msg());
}

// 提取标题和描述
$cover = $data['data']['detail']['cover'] ?? '未知封面';
$title = $data['data']['detail']['title'] ?? '未知标题';
$desc = $data['data']['detail']['desc'] ?? '暂无描述';
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body {
            margin: 0 auto;
            padding: 0;
            width: 100%;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }
        
        .video_desc {
            padding: 0;
            margin: 0;
            transition: all 0.3s ease;
            max-width: 1700px;
            width: 100%;
            margin: 0 auto;
        }
        
        .desc {
            padding: 0 20px;
            margin: 20px 0;
        }
        
        .book-cover {
            max-width: 25%;
            height: auto;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
            display: block;
            margin: 0 auto;
        }
        
        .book-cover h2, .book-cover p {
            padding: 0 20px;
        }
        
        .book-cover h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        
        .book-cover p {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* 选集容器样式 */
        .desc, .episode-container {
            padding: 20px;
            margin: 0 auto;
            max-width: 1200px;
        }
        
        .episode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .episode-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        
        .episode-count {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* 选集网格布局 */
        .episode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }
        
        /* 选集项样式 */
        .episode-item {
            position: relative;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
        }
        
        .episode-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .episode-item:active {
            transform: translateY(1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* 当前播放集样式 */
        .episode-item.active {
            background-color: #ff6b6b;
            color: #fff;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }
        
        .episode-item.active::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background-color: #fff;
            border-radius: 0 0 0 8px;
        }
        
        /* 选集项文本样式 */
        .episode-text {
            font-size: 0.95rem;
            font-weight: 500;
            padding: 0 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* 移动端适配 */
        @media (max-width: 600px) {
            .episode-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 10px;
            }
            
            .episode-item {
                height: 50px;
            }
            
            .episode-text {
                font-size: 0.85rem;
            }
        }

        .video-container::before {
            content: "";
            display: block;
            width: 100%;
            height: 100%;
            background: #000 no-repeat center;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%;
            margin-bottom: 0;
            background-color: #000;
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #ff6b6b;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 上一集和下一集按钮样式 */
        .prev-next-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            font-size: 24px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
        }

        .prev-btn {
            left: 10px;
        }

        .next-btn {
            right: 10px;
        }

        /* 播放完成提示 */
        .play-complete {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 18px;
            z-index: 100;
        }
        
        /* 自动播放提示覆盖层 */
        .play-prompt {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 20;
        }
        
        .play-prompt button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            font-size: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .play-prompt button:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        
        /* 移动端播放完成提示 */
        .mobile-play-next {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 20px;
            border-radius: 8px;
            font-size: 18px;
            z-index: 100;
            flex-direction: column;
            align-items: center;
            text-align: center;
            max-width: 80%;
        }
        
        .mobile-play-next button {
            margin-top: 15px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .mobile-play-next button:hover {
            background-color: #ff5252;
        }
        
        .mobile-play-next button:active {
            transform: scale(0.98);
        }
        
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <div id="contentContainer">
        <div class="video_desc" id="videoDesc">
            <img src="<?= $cover ?>" alt="短剧封面" class="book-cover">
            
        </div>
    </div>
    
    <div class="desc">
        <h2><?= htmlspecialchars($title) ?></h2>
        <p><?= htmlspecialchars($desc) ?></p>
    </div>
    
    <!-- 选集容器 -->
    <div class="episode-container">
        <div class="episode-header">
            <div class="episode-title">剧集列表</div>
            <div class="episode-count"><?= count($data['data']['video_list'] ?? []) ?> 集</div>
        </div>
        <div class="episode-grid" id="episodeGrid">
            <?php if (isset($data['data']['video_list']) && is_array($data['data']['video_list'])): ?>
                <?php foreach ($data['data']['video_list'] as $index => $videoItem): ?>
                    <div 
                        class="episode-item" 
                        data-video-id="<?= htmlspecialchars($videoItem['video_id']) ?>"
                        data-index="<?= $index ?>"
                    >
                        <div class="episode-text">【<?= htmlspecialchars($videoItem['title']) ?>】</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos">暂无视频列表</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // 检测是否为移动设备
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // 在视频容器替换前销毁旧视频元素
    function destroyVideo() {
        const video = document.querySelector('video');
        if (video) {
            video.pause();
            video.remove();
        }
    }
    
    // 创建自动播放提示元素
    function createPlayPrompt() {
        const playPrompt = document.createElement('div');
        playPrompt.className = 'play-prompt';
        playPrompt.innerHTML = '<button>▶</button>';
        return playPrompt;
    }
    
    // 创建移动端播放完成提示元素
    function createMobilePlayNext() {
        const mobilePlayNext = document.createElement('div');
        mobilePlayNext.className = 'mobile-play-next';
        mobilePlayNext.innerHTML = `
            <div>本集完</div>
            <button id="nextEpisodeBtn">下一集</button>
        `;
        return mobilePlayNext;
    }
    
    // 尝试自动播放视频
    function attemptAutoPlay(videoElement, playPrompt) {
        // 重置视频状态
        videoElement.muted = false; // 取消静音
        
        const playPromise = videoElement.play();
        
        if (playPromise !== undefined) {
            playPromise.then(() => {
                // 自动播放成功
                playPrompt.style.display = 'none';
            }).catch(error => {
                // 自动播放失败，显示播放按钮
                console.log('自动播放失败:', error);
                videoElement.muted = true; // 自动播放失败时保持静音
                
                // 显示播放提示
                playPrompt.style.display = 'flex';
                
                // 点击提示后手动播放
                playPrompt.addEventListener('click', () => {
                    // 用户手动触发播放时取消静音
                    videoElement.muted = false;
                    
                    videoElement.play().then(() => {
                        playPrompt.style.display = 'none';
                    }).catch(err => {
                        console.log('用户点击后播放仍失败:', err);
                        // 恢复播放提示
                        playPrompt.style.display = 'flex';
                    });
                });
            });
        }
    }
    
    // 更新选集项的活跃状态
    function updateActiveEpisode(index) {
        const episodeItems = document.querySelectorAll('.episode-item');
        episodeItems.forEach((item, i) => {
            if (i === index) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }
    
        // 获取DOM元素
        const videoDesc = document.getElementById('videoDesc');
        const contentContainer = document.getElementById('contentContainer');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const episodeItems = document.querySelectorAll('.episode-item');
        
        let currentActiveBtn = null;
        let currentVideoIndex = 0; // 当前播放视频的索引
        const videoList = <?= json_encode($data['data']['video_list'] ?? []) ?>; // 获取视频列表

        // 创建上一集和下一集按钮
        const prevBtn = document.createElement('button');
        prevBtn.className = 'prev-next-btn prev-btn';
        prevBtn.textContent = '←';
        prevBtn.title = '上一集'; // 添加title属性
        
        const nextBtn = document.createElement('button');
        nextBtn.className = 'prev-next-btn next-btn';
        nextBtn.textContent = '→';
        nextBtn.title = '下一集'; // 添加title属性

        // 创建播放完成提示元素
        const playComplete = document.createElement('div');
        playComplete.className = 'play-complete';
        playComplete.textContent = '播放下一集...';
        
        // 创建移动端播放完成提示元素
        const mobilePlayNext = createMobilePlayNext();

        // 保存原始标题
        const originalTitle = document.title;
        
        // 计时器
        let hideButtonsTimer = null;

        // 更新按钮显示状态
        function updateButtonVisibility() {
            if (currentVideoIndex === 0) {
                prevBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'block';
            }
            
            if (currentVideoIndex === videoList.length - 1) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = 'block';
            }
        }

        // 显示控制按钮
        function showControlButtons() {
            clearTimeout(hideButtonsTimer);
            prevBtn.style.opacity = '1';
            nextBtn.style.opacity = '1';
            
            // 设置2秒后隐藏按钮
            hideButtonsTimer = setTimeout(() => {
                prevBtn.style.opacity = '0';
                nextBtn.style.opacity = '0';
            }, 2500);
        }

        // 隐藏并重置播放完成提示
        function resetPlayCompletePrompt() {
            // 隐藏移动设备的播放完成提示
            if (mobilePlayNext) {
                mobilePlayNext.style.display = 'none';
                
                // 重置内容，移除可能添加的"剧终"文本
                if (mobilePlayNext.innerHTML !== `
                    <div>本集完</div>
                    <button id="nextEpisodeBtn">下一集</button>
                `) {
                    mobilePlayNext.innerHTML = `
                        <div>本集完</div>
                        <button id="nextEpisodeBtn">下一集</button>
                    `;
                }
                
                // 移除之前添加的事件监听器
                const nextEpisodeBtn = mobilePlayNext.querySelector('#nextEpisodeBtn');
                if (nextEpisodeBtn) {
                    // 无法直接移除匿名函数监听器，因此替换按钮元素
                    const newBtn = nextEpisodeBtn.cloneNode(true);
                    nextEpisodeBtn.parentNode.replaceChild(newBtn, nextEpisodeBtn);
                }
            }
            
            // 隐藏桌面设备的播放完成提示
            if (playComplete) {
                playComplete.style.display = 'none';
                playComplete.textContent = '播放下一集...';
            }
        }

        // 为每个选集项添加点击事件
        episodeItems.forEach((item) => {
            item.addEventListener('click', function() {
                // 隐藏并重置播放完成提示
                resetPlayCompletePrompt();
                
                const index = parseInt(this.getAttribute('data-index'));
                const videoId = this.getAttribute('data-video-id');
                const videoItem = videoList[index];
                
                if (!videoItem) return;
                
                const title = videoItem.title;
                
                // 更新当前索引
                currentVideoIndex = index;
                
                // 更新选集活跃状态
                updateActiveEpisode(index);
                
                // 更新页面标题，添加当前集数信息
                document.title = `${originalTitle} - ${title}`;
                
                // 更新按钮显示状态
                updateButtonVisibility();
                
                // 显示加载动画
                loadingOverlay.style.display = 'flex';
                
                // 使用fetch API发送AJAX请求
                fetch(`play.php?video_id=${encodeURIComponent(videoId)}`)
                    .then(response => {
                        // 隐藏加载动画
                        loadingOverlay.style.display = 'none';
                        
                        if (!response.ok) {
                            throw new Error(`HTTP错误，状态码：${response.status}`);
                        }
                        
                        // 创建视频元素
                        const videoContainer = document.createElement('div');
                        videoContainer.className = 'video-container';
                        
                        const videoElement = document.createElement('video');
                        // 移除autoplay属性，使用JavaScript控制播放
                        videoElement.src = `play.php?video_id=${encodeURIComponent(videoId)}`;
                        videoElement.controls = true;
                        videoElement.muted = true; // 自动播放需要静音
                        videoElement.setAttribute('playsinline', ''); // 启用内联播放
                        
                        // 创建播放提示元素
                        const playPrompt = createPlayPrompt();
                        
                        // 添加视频结束事件监听
                        videoElement.removeEventListener('ended', handleVideoEnd);
                        videoElement.addEventListener('ended', handleVideoEnd);
                        
                        videoContainer.appendChild(videoElement);
                        
                        // 根据设备类型决定是否添加上下集按钮
                        if (!isMobileDevice()) {
                            videoContainer.appendChild(prevBtn);
                            videoContainer.appendChild(nextBtn);
                            
                            // 添加鼠标事件监听
                            videoContainer.addEventListener('mouseenter', showControlButtons);
                            videoContainer.addEventListener('mousemove', showControlButtons);
                            videoContainer.addEventListener('mouseleave', () => {
                                prevBtn.style.opacity = '0';
                                nextBtn.style.opacity = '0';
                            });
                        }
                        
                        videoContainer.appendChild(playComplete);
                        videoContainer.appendChild(playPrompt);
                        
                        // 如果是移动设备，添加移动端播放完成提示
                        if (isMobileDevice()) {
                            videoContainer.appendChild(mobilePlayNext);
                        }
                        
                        // 替换视频描述区域
                        videoDesc.innerHTML = '';
                        videoDesc.appendChild(videoContainer);
                        
                        // 滚动到视频区域
                        videoDesc.scrollIntoView({ behavior: 'smooth' });
                        
                        contentContainer.style.backgroundColor = 'black';
                        
                        // 尝试自动播放
                        attemptAutoPlay(videoElement, playPrompt);
                    })
                    .catch(error => {
                        // 隐藏加载动画
                        loadingOverlay.style.display = 'none';
                        
                        // 显示错误信息
                        videoDesc.innerHTML = `
                            <div style="color: red; text-align: center; padding: 20px;">
                                加载视频失败：${error.message}
                            </div>
                        `;
                    });
            });
        });

        // 视频播放结束处理函数
        function handleVideoEnd() {
            if (isMobileDevice()) {
                // 移动端：显示提示，等待用户点击
                if (currentVideoIndex < videoList.length - 1) {
                    mobilePlayNext.style.display = 'flex';
                    
                    // 获取下一集按钮
                    const nextEpisodeBtn = mobilePlayNext.querySelector('#nextEpisodeBtn');
                    
                    // 创建一个单次执行的事件处理函数
                    const handleNextClick = () => {
                        // 移除事件监听器，防止重复触发
                        nextEpisodeBtn.removeEventListener('click', handleNextClick);
                        
                        // 执行下一集逻辑
                        if (currentVideoIndex < videoList.length - 1) {
                            currentVideoIndex++;
                            const nextVideoItem = videoList[currentVideoIndex];
                            const nextVideoId = nextVideoItem.video_id;
                            const nextVideoTitle = nextVideoItem.title;
                            
                            // 更新页面标题
                            document.title = `${originalTitle} - ${nextVideoTitle}`;
                            
                            // 更新选集活跃状态
                            updateActiveEpisode(currentVideoIndex);
                            
                            // 更新按钮显示状态
                            updateButtonVisibility();
                            
                            // 模拟点击下一集按钮
                            const nextEpisodeItem = document.querySelector(`.episode-item[data-index="${currentVideoIndex}"]`);
                            if (nextEpisodeItem) {
                                nextEpisodeItem.click();
                            }
                            
                            // 隐藏播放完成提示
                            mobilePlayNext.style.display = 'none';
                        }
                    };
                    
                    // 添加事件监听器
                    nextEpisodeBtn.addEventListener('click', handleNextClick);
                } else {
                    mobilePlayNext.innerHTML = '<div>剧终</div>';
                    mobilePlayNext.style.display = 'flex';
                }
            } else {
                // 桌面端：自动播放下一集
                if (currentVideoIndex < videoList.length - 1) {
                    // 显示播放完成提示
                    playComplete.style.display = 'block';
                    
                    // 延迟1秒后播放下一集
                    setTimeout(() => {
                        currentVideoIndex++;
                        const nextVideoItem = videoList[currentVideoIndex];
                        const nextVideoId = nextVideoItem.video_id;
                        const nextVideoTitle = nextVideoItem.title;

                        // 更新页面标题
                        document.title = `${originalTitle} - ${nextVideoTitle}`;
                        
                        // 更新选集活跃状态
                        updateActiveEpisode(currentVideoIndex);
                        
                        // 更新按钮显示状态
                        updateButtonVisibility();
                        
                        // 模拟点击下一集按钮
                        const nextEpisodeItem = document.querySelector(`.episode-item[data-index="${currentVideoIndex}"]`);
                        if (nextEpisodeItem) {
                            nextEpisodeItem.click();
                        }
                        
                        // 隐藏播放完成提示
                        playComplete.style.display = 'none';
                    }, 1000);
                } else {
                    // 最后一集播放完毕，显示提示
                    playComplete.textContent = '剧终';
                    playComplete.style.display = 'block';
                }
            }
        }

        // 上一集按钮点击事件
        prevBtn.addEventListener('click', function() {
            if (currentVideoIndex > 0) {
                currentVideoIndex--;
                const prevVideoItem = videoList[currentVideoIndex];
                const prevVideoId = prevVideoItem.video_id;
                const prevVideoTitle = prevVideoItem.title;

                // 更新页面标题
                document.title = `${originalTitle} - ${prevVideoTitle}`;
                
                // 更新选集活跃状态
                updateActiveEpisode(currentVideoIndex);
                
                // 更新按钮显示状态
                updateButtonVisibility();
                
                // 隐藏并重置播放完成提示
                resetPlayCompletePrompt();
                
                // 模拟点击上一集按钮
                const prevEpisodeItem = document.querySelector(`.episode-item[data-index="${currentVideoIndex}"]`);
                if (prevEpisodeItem) {
                    prevEpisodeItem.click();
                }
            }
        });

        // 下一集按钮点击事件
        nextBtn.addEventListener('click', function() {
            if (currentVideoIndex < videoList.length - 1) {
                currentVideoIndex++;
                const nextVideoItem = videoList[currentVideoIndex];
                const nextVideoId = nextVideoItem.video_id;
                const nextVideoTitle = nextVideoItem.title;

                // 更新页面标题
                document.title = `${originalTitle} - ${nextVideoTitle}`;
                
                // 更新选集活跃状态
                updateActiveEpisode(currentVideoIndex);
                
                // 更新按钮显示状态
                updateButtonVisibility();
                
                // 隐藏并重置播放完成提示
                resetPlayCompletePrompt();
                
                // 模拟点击下一集按钮
                const nextEpisodeItem = document.querySelector(`.episode-item[data-index="${currentVideoIndex}"]`);
                if (nextEpisodeItem) {
                    nextEpisodeItem.click();
                }
            }
        });

        // 点击操作判定 - 隐藏剧终提示
        document.addEventListener('click', function(event) {
            // 检查点击是否发生在视频容器内部
            const videoContainer = document.querySelector('.video-container');
            const isClickInsideVideo = videoContainer && videoContainer.contains(event.target);
            
            // 检查点击是否发生在选集容器内部
            const episodeContainer = document.querySelector('.episode-container');
            const isClickInsideEpisodes = episodeContainer && episodeContainer.contains(event.target);
            
            // 如果点击发生在视频容器或选集容器内部，则隐藏提示
            if (isClickInsideVideo || isClickInsideEpisodes) {
                resetPlayCompletePrompt();
            }
        });
        
    </script>
</body>
</html>