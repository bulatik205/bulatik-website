class GitHubProjects {
    constructor(username) {
        this.username = username;
    }

    async getRepos() {
        try {
            // REST API работает без токена для публичных репозиториев
            const response = await fetch(
                `https://api.github.com/users/${this.username}/repos?sort=updated&per_page=100`
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const repos = await response.json();

            // Сортируем по звёздам (обычно закрепленные в топе)
            return repos
                .filter(repo => !repo.fork) // Убираем форки (опционально)
                .sort((a, b) => b.stargazers_count - a.stargazers_count)
                .slice(0, 5); // Берём топ-5

        } catch (error) {
            console.error('Ошибка при получении репозиториев:', error);
            return [];
        }
    }

    createProjectCard(repo) {
        const topics = repo.topics || [];
        const topicsHTML = topics.slice(0, 4).map(topic =>
            `<span class="project--topic">#${topic}</span>`
        ).join('');

        const description = repo.description || 'Описание отсутствует';
        const truncatedDescription = description.length > 100
            ? description.substring(0, 100) + '...'
            : description;

        return `
        <div class="project--card">
            <div class="project--card-header">
                <h3>
                    <a href="${repo.html_url}" target="_blank" class="project--name">
                        ${repo.name}
                    </a>
                </h3>
                ${repo.archived ? '<span class="project--archived">Архив</span>' : ''}
            </div>
            <p class="project--description">${truncatedDescription}</p>
            ${topicsHTML ? `<div class="project--topics">${topicsHTML}</div>` : ''}
            <div class="project--stats">
                ${repo.language ?
                `<span class="project--lang">
                        <span class="lang--dot" style="background-color: ${this.getLanguageColor(repo.language)}"></span>
                        ${repo.language}
                    </span>`
                : ''
            }
            </div>
        </div>
    `;
    }

    getLanguageColor(language) {
        const colors = {
            'JavaScript': '#f1e05a',
            'TypeScript': '#2b7489',
            'Python': '#3572A5',
            'PHP': '#4F5D95',
            'HTML': '#e34c26',
            'CSS': '#563d7c',
            'Java': '#b07219',
            'Go': '#00ADD8',
            'Rust': '#dea584',
            'C++': '#f34b7d',
            'C#': '#178600',
            'Ruby': '#701516',
            'Swift': '#ffac45',
            'Kotlin': '#A97BFF'
        };
        return colors[language] || '#858585';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) return 'Сегодня';
        if (days === 1) return 'Вчера';
        if (days < 7) return `${days} дн. назад`;
        if (days < 30) return `${Math.floor(days / 7)} нед. назад`;
        if (days < 365) return `${Math.floor(days / 30)} мес. назад`;
        return `${Math.floor(days / 365)} г. назад`;
    }

    async renderProjects() {
        const projectsGrid = document.getElementById('projectsGrid');

        if (!projectsGrid) {
            console.error('Элемент #projectsGrid не найден');
            return;
        }

        projectsGrid.innerHTML = `
            <div class="loading">
                <p>⏳ Загружаю проекты...</p>
            </div>
        `;

        const repos = await this.getRepos();

        if (repos.length === 0) {
            projectsGrid.innerHTML = `
                <div class="error">
                    <p>😕 Не удалось загрузить проекты</p>
                    <p style="font-size: 14px; margin-top: 10px;">
                        Проверьте подключение к интернету или попробуйте позже
                    </p>
                    <button onclick="location.reload()" class="retry--button">
                        Повторить
                    </button>
                </div>
            `;
            return;
        }

        const projectsHTML = repos
            .map(repo => this.createProjectCard(repo))
            .join('');

        projectsGrid.innerHTML = projectsHTML + `
            <a href="/p/" class="project--card all-projects-link">
                <div class="project--card-header" style="justify-content: center; height: 100%;">
                    <h3 style="text-align: center; margin: 0;">
                        Все проекты
                    </h3>
                </div>
            </a>
        `;
    }
}

// Инициализация
const githubProjects = new GitHubProjects('bulatik205');

// Запускаем когда страница загрузится
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        githubProjects.renderProjects();
    });
} else {
    githubProjects.renderProjects();
}