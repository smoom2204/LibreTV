<?php
// 输入验证
$name = trim($_GET['name'] ?? '');
if (empty($name)) {
    die('错误：缺少必要的 name 参数');
}
if (!preg_match('/^[\w\x{4e00}-\x{9fa5}]{1,50}$/u', $name)) {
    die('错误：参数包含非法字符或长度超过限制');
}

// 初始化页码（默认为第1页，最大10页）
$currentPage = isset($_GET['page']) ? max(1, min(10, (int)$_GET['page'])) : 1;

// 构造请求（添加页码参数）
$encodedName = urlencode($name);
$apiUrl = "https://api.xingzhige.com/API/playlet/?keyword={$encodedName}&page={$currentPage}";

// cURL 设置（开启 SSL 验证）
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 添加超时时间
$response = curl_exec($ch);

// 处理 cURL 错误
if (curl_errno($ch)) {
    error_log("cURL Error: " . curl_error($ch));
    die('服务器请求失败，请稍后重试');
}
curl_close($ch);

// 解析 JSON
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON Parse Error: " . json_last_error_msg());
    die('数据解析失败，请联系管理员');
}
if (empty($data['data']) || !is_array($data['data'])) {
    if ($currentPage === 1) {
        die('未找到与 "' . htmlspecialchars($name) . '" 相关的短剧');
    } else {
        die('没有更多内容了');
    }
}

// 生成短剧列表 HTML（分离 CSS 样式）
$booksHtml = '';
foreach ($data['data'] as $item) {
    $bookId = htmlspecialchars($item['book_id'] ?? '');
    $title = htmlspecialchars($item['title'] ?? 'Unknown Title');
    $cover = htmlspecialchars($item['cover'] ?? '');
    
    $booksHtml .= '<div class="book-card">
        <a href="list.php?book_id='.$bookId.'" target="_blank" rel="noopener noreferrer">
            <div class="book-cover-container">
                <img src="'.$cover.'" alt="'.($title ?: '短剧封面').'" class="book-cover">
            </div>
            <h3 class="book-title">'.$title.'</h3>
        </a>
    </div>';
}

// 生成页码导航（保留PHP变量，用于初始渲染和JavaScript引用）
$paginationHtml = '';
$totalPages = 10; // 最大页数调整为10

// 首页和上一页按钮
if ($currentPage > 1) {
    $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" data-page="1">首页</a></li>';
    $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" data-page="'.($currentPage - 1).'">上一页</a></li>';
} else {
    $paginationHtml .= '<li class="page-item disabled"><span class="page-link">首页</span></li>';
    $paginationHtml .= '<li class="page-item disabled"><span class="page-link">上一页</span></li>';
}

// 页码按钮（简化显示，只显示当前页附近的页码）
$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);

for ($i = $startPage; $i <= $endPage; $i++) {
    if ($i === $currentPage) {
        $paginationHtml .= '<li class="page-item active"><span class="page-link">'.$i.'</span></li>';
    } else {
        $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" data-page="'.$i.'">'.$i.'</a></li>';
    }
}

// 下一页和末页按钮
if ($currentPage < $totalPages) {
    $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" data-page="'.($currentPage + 1).'">下一页</a></li>';
    $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" data-page="'.$totalPages.'">末页</a></li>';
} else {
    $paginationHtml .= '<li class="page-item disabled"><span class="page-link">下一页</span></li>';
    $paginationHtml .= '<li class="page-item disabled"><span class="page-link">末页</span></li>';
}

// 如果是AJAX请求，只返回短剧列表和页码HTML
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode([
        'booksHtml' => $booksHtml,
        'paginationHtml' => $paginationHtml,
        'currentPage' => $currentPage
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>搜索结果 - <?= htmlspecialchars($name) ?></title>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --background-color: #f8f9fa;
            --text-color: #333;
            --card-bg: #fff;
            --border-color: #e0e0e0;
            --shadow: 0 2px 10px rgba(0,0,0,0.08);
            --hover-shadow: 0 5px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            padding: 1rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin: 1.5rem 0;
            font-size: clamp(1.5rem, 3vw, 2.5rem);
            color: var(--primary-color);
        }

        .book-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .book-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .book-card a {
            display: flex;
            flex-direction: column;
            height: 100%;
            text-decoration: none;
            color: var(--text-color);
        }

        .book-cover-container {
            position: relative;
            padding-bottom: 140%; /* 3:4 比例 */
            overflow: hidden;
        }

        .book-cover {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .book-card:hover .book-cover {
            transform: scale(1.03);
        }

        .book-title {
            font-size: 1.1rem;
            margin: 0;
            padding: 1rem;
            line-height: 1.4;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        .pagination-container {
            margin: 2rem 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
            display: flex;
            justify-content: center;
        }

        .pagination-container::-webkit-scrollbar {
            height: 6px;
        }

        .pagination-container::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }

        .pagination-container::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 10px;
        }

        .pagination {
            display: flex;
            list-style: none;
            border-radius: 0.5rem;
            /*box-shadow: var(--shadow);*/
            /*background-color: var(--card-bg);*/
            min-width: max-content;
        }

        .page-item {
            margin: 0;
        }

        .page-link {
            display: block;
            padding: 0.75rem 1rem;
            line-height: 1;
            color: var(--primary-color);
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            margin-left: -1px;
            transition: var(--transition);
            white-space: nowrap;
        }

        .page-link:hover {
            background-color: #f0f0f0;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            z-index: 1;
        }

        .page-item.disabled .page-link {
            color: var(--secondary-color);
            pointer-events: none;
            background-color: var(--card-bg);
        }

        .page-item:first-child .page-link {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }

        .page-item:last-child .page-link {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 响应式调整 */
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }

            .book-list {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
                padding: 0.5rem;
            }

            .book-title {
                font-size: 0.95rem;
                padding: 0.75rem;
            }

            .pagination-container {
                margin: 1.5rem 0;
            }

            .page-link {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        /* 小屏幕设备优化 */
        @media (max-width: 480px) {
            .page-link {
                padding: 0.5rem 0.6rem;
            }
            
            /* 简化分页显示，在极小屏幕只显示关键页码 */
            .pagination .page-item:nth-child(3) ~ .page-item:not(:nth-last-child(3)):not(:nth-last-child(2)):not(:last-child) {
                display: none;
            }
            
            /* 显示省略号提示 */
            .pagination::after {
                content: "...";
                display: flex;
                align-items: center;
                padding: 0 0.5rem;
                color: var(--secondary-color);
            }
        }

        /* 平板设备 */
        @media (min-width: 769px) and (max-width: 1024px) {
            .book-list {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        } /* 修复：添加缺失的右括号 */
    </style>
</head>
<body>
    <h1>搜索结果：<?= htmlspecialchars($name) ?></h1>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="book-list" id="bookList">
        <?= $booksHtml ?>
    </div>
    
    <div class="pagination-container">
        <ul class="pagination" id="pagination">
            <?= $paginationHtml ?>
        </ul>
    </div>

    <script>
        // 缓存DOM元素
        const bookList = document.getElementById('bookList');
        const pagination = document.getElementById('pagination');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const currentKeyword = '<?= $encodedName ?>'; // 从PHP获取当前搜索关键词
        
        // 为页码链接添加点击事件
        pagination.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = e.target.closest('.page-link');
            if (!target || target.parentElement.classList.contains('disabled') || 
                target.parentElement.classList.contains('active')) {
                return;
            }
            
            const page = parseInt(target.getAttribute('data-page'));
            if (page) {
                loadPage(page);
            }
        });
        
        // 加载指定页码的内容
        function loadPage(page) {
            // 显示加载中状态
            loadingOverlay.style.display = 'flex';
            
            // 构建请求URL（保留当前页面的其他GET参数）
            const currentUrl = new URL(window.location.href);
            const searchParams = new URLSearchParams(currentUrl.search);
            searchParams.set('page', page);
            
            // 使用fetch API发送AJAX请求
            fetch(window.location.pathname + '?' + searchParams.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('网络响应错误: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // 更新短剧列表和页码导航
                bookList.innerHTML = data.booksHtml;
                pagination.innerHTML = data.paginationHtml;
                
                // 更新浏览器历史记录（不刷新页面）
                history.pushState({page: page}, null, window.location.pathname + '?' + searchParams.toString());
                
                // 平滑滚动到顶部
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            })
            .catch(error => {
                console.error('加载页面失败:', error);
                alert('加载页面失败，请重试');
            })
            .finally(() => {
                // 隐藏加载状态
                loadingOverlay.style.display = 'none';
            });
        }
        
        // 监听浏览器后退/前进事件
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.page) {
                loadPage(e.state.page);
            }
        });
    </script>
</body>
</html>    