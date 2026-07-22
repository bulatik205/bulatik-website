<?php
/**
 * GitHub Projects Viewer
 * 
 * Использование:
 * /p/ - список всех репозиториев
 * /p/?repo=busync - просмотр README конкретного репозитория
 */

require_once __DIR__ . '/vendor/autoload.php';

class GitHubViewer {
    private string $username = 'bulatik205';
    private string $apiBase = 'https://api.github.com';
    private string $rawBase = 'https://raw.githubusercontent.com';
    private Parsedown $parsedown;
    
    public function __construct() {
        $this->parsedown = new Parsedown();
        $this->parsedown->setSafeMode(false);
    }
    
    /**
     * Главный метод обработки запроса
     */
    public function handleRequest(): void {
        $repo = $_GET['repo'] ?? null;
        
        if ($repo) {
            $this->showReadme($repo);
        } else {
            $this->showProjectsList();
        }
    }
    
    /**
     * Показывает README репозитория
     */
    private function showReadme(string $repo): void {
        $readme = $this->fetchReadme($repo);
        $repoInfo = $this->fetchRepoInfo($repo);
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bulatik205 | <?= htmlspecialchars($repo) ?></title>
            <link rel="stylesheet" href="/styles/index.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
            <style>
                .readme-container {
                    max-width: 900px;
                    margin: 40px auto;
                    padding: 20px;
                }
                
                .readme-back {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    color: var(--accent-blue);
                    text-decoration: none;
                    margin-bottom: 30px;
                    font-size: 14px;
                    transition: color 0.3s;
                }
                
                .readme-back:hover {
                    color: #79c0ff;
                }
                
                .readme-back::before {
                    content: '←';
                    font-size: 18px;
                }
                
                .readme-header {
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid var(--card-border);
                }
                
                .readme-title {
                    font-size: 32px;
                    margin: 0 0 10px 0;
                    color: var(--text-primary);
                }
                
                .readme-description {
                    color: var(--text-secondary);
                    font-size: 16px;
                    margin: 0 0 15px 0;
                }
                
                .readme-meta {
                    display: flex;
                    gap: 20px;
                    flex-wrap: wrap;
                }
                
                .readme-meta-item {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 13px;
                    color: var(--text-secondary);
                    padding: 4px 12px;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
                
                .readme-content {
                    background: var(--card-bg);
                    border: 1px solid var(--card-border);
                    border-radius: 16px;
                    padding: 40px;
                    color: var(--text-primary);
                    line-height: 1.7;
                }
                
                .readme-content h1 { font-size: 2em; border-bottom: 1px solid var(--card-border); padding-bottom: 10px; margin: 24px 0 16px; }
                .readme-content h2 { font-size: 1.5em; border-bottom: 1px solid var(--card-border); padding-bottom: 8px; margin: 24px 0 16px; }
                .readme-content h3 { font-size: 1.25em; margin: 24px 0 16px; }
                .readme-content h4 { font-size: 1em; margin: 24px 0 16px; }
                .readme-content h5, .readme-content h6 { font-size: 0.875em; margin: 24px 0 16px; }
                
                .readme-content p { margin: 0 0 16px; }
                
                .readme-content a {
                    color: var(--accent-blue);
                    text-decoration: none;
                }
                
                .readme-content a:hover {
                    text-decoration: underline;
                }
                
                .readme-content ul, .readme-content ol {
                    padding-left: 2em;
                    margin: 0 0 16px;
                }
                
                .readme-content li { margin: 4px 0; }
                
                .readme-content code {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-size: 0.9em;
                    font-family: 'Fira Code', monospace;
                }
                
                .readme-content pre {
                    background: rgba(0, 0, 0, 0.3);
                    padding: 16px;
                    border-radius: 8px;
                    overflow-x: auto;
                    margin: 0 0 16px;
                    border: 1px solid var(--card-border);
                }
                
                .readme-content pre code {
                    background: none;
                    padding: 0;
                    font-size: 13px;
                    line-height: 1.5;
                }
                
                .readme-content img {
                    max-width: 100%;
                    height: auto;
                    border-radius: 8px;
                }
                
                .readme-content blockquote {
                    border-left: 4px solid var(--accent-green);
                    padding: 10px 20px;
                    margin: 0 0 16px;
                    background: rgba(30, 141, 63, 0.1);
                    border-radius: 0 8px 8px 0;
                    color: var(--text-secondary);
                }
                
                .readme-content table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0 0 16px;
                }
                
                .readme-content th, .readme-content td {
                    padding: 8px 12px;
                    border: 1px solid var(--card-border);
                    text-align: left;
                }
                
                .readme-content th {
                    background: rgba(255, 255, 255, 0.05);
                    font-weight: 600;
                }
                
                .readme-no-content {
                    text-align: center;
                    padding: 60px 20px;
                    color: var(--text-secondary);
                }
                
                .readme-no-content p {
                    font-size: 18px;
                    margin: 0;
                }
                
                @media (max-width: 768px) {
                    .readme-content {
                        padding: 20px;
                    }
                    
                    .readme-title {
                        font-size: 24px;
                    }
                }
            </style>
        </head>
        <body>
            <header class="header" id="glassHeader">
                <div class="header--content">
                    <p>bulatik</p>
                    <div>
                        <a href="/p/">Проекты</a>
                        <a href="https://github.com/bulatik205/bulatik-website">
                            <img src="/images/GitHub_Invertocat_White.png" alt="">
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="readme-container">
                <a href="/p/" class="readme-back">Вернуться к списку проектов</a>
                
                <?php if ($repoInfo): ?>
                <div class="readme-header">
                    <h1 class="readme-title"><?= htmlspecialchars($repoInfo['name']) ?></h1>
                    <?php if ($repoInfo['description']): ?>
                    <p class="readme-description"><?= htmlspecialchars($repoInfo['description']) ?></p>
                    <?php endif; ?>
                    <div class="readme-meta">
                        <?php if ($repoInfo['language']): ?>
                        <span class="readme-meta-item">
                            📄 <?= htmlspecialchars($repoInfo['language']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="readme-meta-item">
                            ⭐ <?= $repoInfo['stars'] ?>
                        </span>
                        <span class="readme-meta-item">
                            🍴 <?= $repoInfo['forks'] ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="readme-content">
                    <?= $readme ?>
                </div>
            </div>
            
            <script src="/js/header.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
            <script>hljs.highlightAll();</script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Показывает список проектов
     */
    private function showProjectsList(): void {
        $repos = $this->fetchRepos();
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bulatik205 | Проекты</title>
            <link rel="stylesheet" href="/styles/index.css">
        </head>
        <body>
            <header class="header" id="glassHeader">
                <div class="header--content">
                    <p>bulatik</p>
                    <div>
                        <a href="/">Главная</a>
                        <a href="https://github.com/bulatik205/bulatik-website">
                            <img src="/images/GitHub_Invertocat_White.png" alt="">
                        </a>
                    </div>
                </div>
            </header>

            <div class="projects--block">
                <h2 class="fs-firacode">Мои проекты</h2>
                <div class="projects--grid" id="projectsGrid">
                    <?= $this->renderProjectsHTML($repos) ?>
                </div>
            </div>

            <script src="/js/header.js"></script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Получает список репозиториев через GitHub API
     */
    private function fetchRepos(): array {
        $url = "{$this->apiBase}/users/{$this->username}/repos?sort=updated&per_page=100";
        
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: PHP-GitHub-Viewer\r\nAccept: application/vnd.github.v3+json\r\n",
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return [];
        }
        
        $repos = json_decode($response, true);
        
        if (!is_array($repos)) {
            return [];
        }
        
        // Убираем форки и сортируем по звездам
        $repos = array_filter($repos, fn($repo) => !($repo['fork'] ?? false));
        usort($repos, fn($a, $b) => ($b['stargazers_count'] ?? 0) <=> ($a['stargazers_count'] ?? 0));
        
        // Возвращаем все репозитории без ограничения
        return array_values($repos);
    }
    
    /**
     * Получает информацию о конкретном репозитории
     */
    private function fetchRepoInfo(string $repo): ?array {
        $url = "{$this->apiBase}/repos/{$this->username}/{$repo}";
        
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: PHP-GitHub-Viewer\r\nAccept: application/vnd.github.v3+json\r\n",
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        return [
            'name' => $data['name'] ?? $repo,
            'description' => $data['description'] ?? '',
            'language' => $data['language'] ?? '',
            'stars' => $data['stargazers_count'] ?? 0,
            'forks' => $data['forks_count'] ?? 0
        ];
    }
    
    /**
     * Получает и конвертирует README.md в HTML используя Parsedown
     */
    private function fetchReadme(string $repo): string {
        $url = "{$this->rawBase}/{$this->username}/{$repo}/main/README.md";
        
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: PHP-GitHub-Viewer\r\n",
                'timeout' => 10
            ]
        ]);
        
        $markdown = @file_get_contents($url, false, $context);
        
        if ($markdown === false) {
            // Пробуем ветку master
            $url = "{$this->rawBase}/{$this->username}/{$repo}/master/README.md";
            $markdown = @file_get_contents($url, false, $context);
        }
        
        if ($markdown === false) {
            return '
            <div class="readme-no-content">
                <p>📝 В проекте отсутствует README</p>
            </div>';
        }
        
        // Парсим Markdown через Parsedown
        return $this->parsedown->text($markdown);
    }
    
    /**
     * Генерирует HTML для карточек проектов
     */
    private function renderProjectsHTML(array $repos): string {
        if (empty($repos)) {
            return '
            <div class="error" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                <p>😕 Не удалось загрузить проекты</p>
                <p style="font-size: 14px; margin-top: 10px;">Проверьте подключение к интернету или попробуйте позже</p>
                <button onclick="location.reload()" class="retry--button">Повторить</button>
            </div>';
        }
        
        $html = '';
        
        foreach ($repos as $repo) {
            $name = htmlspecialchars($repo['name'] ?? '');
            $description = htmlspecialchars($repo['description'] ?? 'Описание отсутствует');
            $language = htmlspecialchars($repo['language'] ?? '');
            $viewUrl = "/p/?repo=" . urlencode($name);
            $archived = !empty($repo['archived']);
            $topics = $repo['topics'] ?? [];
            
            if (mb_strlen($description) > 100) {
                $description = mb_substr($description, 0, 100) . '...';
            }
            
            $topicsHTML = '';
            foreach (array_slice($topics, 0, 4) as $topic) {
                $topic = htmlspecialchars($topic);
                $topicsHTML .= "<span class=\"project--topic\">#{$topic}</span>";
            }
            
            $html .= "
            <div class=\"project--card\">
                <div class=\"project--card-header\">
                    <h3>
                        <a href=\"{$viewUrl}\" class=\"project--name\">{$name}</a>
                    </h3>
                    " . ($archived ? '<span class="project--archived">Архив</span>' : '') . "
                </div>
                <p class=\"project--description\">{$description}</p>
                " . ($topicsHTML ? "<div class=\"project--topics\">{$topicsHTML}</div>" : '') . "
                <div class=\"project--stats\">
                    " . ($language ? "
                    <span class=\"project--lang\">
                        <span class=\"lang--dot\" style=\"background-color: {$this->getLanguageColor($language)}\"></span>
                        {$language}
                    </span>
                    " : '') . "
                </div>
            </div>";
        }
        
        return $html;
    }
    
    /**
     * Возвращает цвет для языка программирования
     */
    private function getLanguageColor(string $language): string {
        $colors = [
            'JavaScript' => '#f1e05a',
            'TypeScript' => '#2b7489',
            'Python' => '#3572A5',
            'PHP' => '#4F5D95',
            'HTML' => '#e34c26',
            'CSS' => '#563d7c',
            'Java' => '#b07219',
            'Go' => '#00ADD8',
            'Rust' => '#dea584',
            'C++' => '#f34b7d',
            'C#' => '#178600',
            'Ruby' => '#701516',
            'Swift' => '#ffac45',
            'Kotlin' => '#A97BFF'
        ];
        
        return $colors[$language] ?? '#858585';
    }
}

// Запуск приложения
$viewer = new GitHubViewer();
$viewer->handleRequest();