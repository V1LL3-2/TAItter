// TAItter - Main JavaScript Application

class TAItterApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadSidebarData();
        this.setupPostModal();
    }

    setupEventListeners() {
        // Post modal character counter
        const postContent = document.getElementById('post-content');
        const charCount = document.getElementById('char-count');
        
        if (postContent && charCount) {
            postContent.addEventListener('input', () => {
                const count = postContent.value.length;
                charCount.textContent = count;
                
                if (count > 144) {
                    charCount.style.color = 'var(--danger-color)';
                } else if (count > 120) {
                    charCount.style.color = 'var(--warning-color)';
                } else {
                    charCount.style.color = 'var(--text-muted)';
                }
            });
        }

        // Like user buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.like-user-btn')) {
                this.handleLikeUser(e.target.closest('.like-user-btn'));
            }
            
            if (e.target.closest('.follow-hashtag-btn')) {
                this.handleFollowHashtags(e.target.closest('.follow-hashtag-btn'));
            }
        });

        // Auto-refresh feed every 30 seconds
        setInterval(() => {
            this.refreshFeed();
        }, 30000);
    }

    setupPostModal() {
        const modal = document.getElementById('post-modal');
        const postContent = document.getElementById('post-content');
        
        if (modal && postContent) {
            // Close modal on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    this.closePostModal();
                }
            });

            // Focus textarea when modal opens
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (modal.classList.contains('show')) {
                            postContent.focus();
                        }
                    }
                });
            });
            observer.observe(modal, { attributes: true });
        }
    }

    async loadSidebarData() {
        if (!this.isLoggedIn()) return;

        try {
            // Load trending hashtags
            const hashtagsResponse = await fetch('api/hashtags.php?action=all&limit=5');
            const hashtagsData = await hashtagsResponse.json();
            
            if (hashtagsData.hashtags) {
                this.renderTrendingHashtags(hashtagsData.hashtags);
            }

            // Load suggested users
            const usersResponse = await fetch('api/users.php?action=search&q=');
            const usersData = await usersResponse.json();
            
            if (usersData.users) {
                this.renderSuggestedUsers(usersData.users.slice(0, 5));
            }
        } catch (error) {
            console.error('Error loading sidebar data:', error);
        }
    }

    renderTrendingHashtags(hashtags) {
        const container = document.getElementById('trending-hashtags');
        if (!container) return;

        if (hashtags.length === 0) {
            container.innerHTML = '<p class="text-muted">No trending hashtags</p>';
            return;
        }

        const html = hashtags.map(hashtag => `
            <div class="hashtag-item">
                <a href="hashtag.php?tag=${hashtag.tag}" class="hashtag-link">
                    #${hashtag.tag}
                </a>
                <span class="post-count">${hashtag.post_count} posts</span>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderSuggestedUsers(users) {
        const container = document.getElementById('suggested-users');
        if (!container) return;

        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted">No suggested users</p>';
            return;
        }

        const html = users.map(user => `
            <div class="user-item">
                <div class="user-info">
                    <div class="avatar">${user.username.charAt(0).toUpperCase()}</div>
                    <div class="user-details">
                        <a href="profile.php?username=${user.username}" class="username">@${user.username}</a>
                        <p class="user-description">${user.description || 'No description'}</p>
                    </div>
                </div>
                <button class="btn btn-sm like-user-btn" data-user-id="${user.id}">
                    <i class="far fa-heart"></i>
                </button>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    openPostModal() {
        const modal = document.getElementById('post-modal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    closePostModal() {
        const modal = document.getElementById('post-modal');
        const postContent = document.getElementById('post-content');
        
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
        
        if (postContent) {
            postContent.value = '';
            document.getElementById('char-count').textContent = '0';
        }
    }

    async submitPost() {
        const postContent = document.getElementById('post-content');
        if (!postContent) return;

        const content = postContent.value.trim();
        if (!content) {
            this.showAlert('Please enter some content for your post', 'error');
            return;
        }

        if (content.length > 144) {
            this.showAlert('Post content must be 144 characters or less', 'error');
            return;
        }

        this.showLoading(true);

        try {
            const response = await fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ content })
            });

            const data = await response.json();

            if (response.ok) {
                this.closePostModal();
                this.refreshFeed();
                this.showAlert('Post created successfully!', 'success');
            } else {
                this.showAlert(data.error || 'Failed to create post', 'error');
            }
        } catch (error) {
            console.error('Error creating post:', error);
            this.showAlert('Network error. Please try again.', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async refreshFeed() {
        const container = document.getElementById('posts-container');
        if (!container) return;

        try {
            const response = await fetch('api/posts.php?action=timeline&limit=20');
            const data = await response.json();

            if (response.ok && data.posts) {
                this.renderPosts(data.posts);
            }
        } catch (error) {
            console.error('Error refreshing feed:', error);
        }
    }

    renderPosts(posts) {
        const container = document.getElementById('posts-container');
        if (!container) return;

        if (posts.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>No posts yet</h3>
                    <p>Be the first to share something!</p>
                </div>
            `;
            return;
        }

        const html = posts.map(post => `
            <div class="post" data-post-id="${post.id}">
                <div class="post-header">
                    <div class="user-info">
                        <div class="avatar">${post.username.charAt(0).toUpperCase()}</div>
                        <div class="user-details">
                            <a href="profile.php?username=${post.username}" class="username">
                                @${post.username}
                            </a>
                            <span class="post-time">${this.timeAgo(post.created_at)}</span>
                        </div>
                    </div>
                    ${this.isLoggedIn() && post.user_id != this.getCurrentUserId() ? `
                        <button class="btn btn-sm like-user-btn" data-user-id="${post.user_id}">
                            <i class="far fa-heart"></i>
                        </button>
                    ` : ''}
                </div>
                
                <div class="post-content">
                    ${this.formatPostContent(post.content)}
                </div>
                
                <div class="post-actions">
                    <button class="action-btn like-user-btn" data-user-id="${post.user_id}">
                        <i class="far fa-heart"></i>
                        <span>Like User</span>
                    </button>
                    <button class="action-btn follow-hashtag-btn" data-hashtags='${JSON.stringify(this.extractHashtags(post.content))}'>
                        <i class="fas fa-hashtag"></i>
                        <span>Follow Tags</span>
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async handleLikeUser(button) {
        if (!this.isLoggedIn()) {
            this.showAlert('Please login to like users', 'error');
            return;
        }

        const userId = button.dataset.userId;
        if (!userId) return;

        const isLiked = button.querySelector('i').classList.contains('fas');
        
        try {
            const response = await fetch('api/users.php', {
                method: isLiked ? 'DELETE' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            });

            const data = await response.json();

            if (response.ok) {
                const icon = button.querySelector('i');
                if (isLiked) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
                this.showAlert(data.message, 'success');
            } else {
                this.showAlert(data.error || 'Failed to like user', 'error');
            }
        } catch (error) {
            console.error('Error liking user:', error);
            this.showAlert('Network error. Please try again.', 'error');
        }
    }

    async handleFollowHashtags(button) {
        if (!this.isLoggedIn()) {
            this.showAlert('Please login to follow hashtags', 'error');
            return;
        }

        const hashtags = JSON.parse(button.dataset.hashtags || '[]');
        if (hashtags.length === 0) {
            this.showAlert('No hashtags found in this post', 'info');
            return;
        }

        try {
            for (const hashtag of hashtags) {
                // First, get or create hashtag
                const hashtagResponse = await fetch(`api/hashtags.php?action=search&q=${hashtag}`);
                const hashtagData = await hashtagResponse.json();
                
                if (hashtagData.hashtags && hashtagData.hashtags.length > 0) {
                    const hashtagId = hashtagData.hashtags[0].id;
                    
                    // Follow the hashtag
                    await fetch('api/hashtags.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ hashtag_id: hashtagId })
                    });
                }
            }
            
            this.showAlert(`Following ${hashtags.length} hashtag(s)`, 'success');
        } catch (error) {
            console.error('Error following hashtags:', error);
            this.showAlert('Network error. Please try again.', 'error');
        }
    }

    async logout() {
        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'logout' })
            });

            if (response.ok) {
                window.location.href = 'login.php';
            }
        } catch (error) {
            console.error('Error logging out:', error);
            window.location.href = 'login.php';
        }
    }

    formatPostContent(content) {
        // Convert hashtags to clickable links
        content = content.replace(/#(\w+)/g, '<a href="hashtag.php?tag=$1" class="hashtag">#$1</a>');
        
        // Convert mentions to clickable links
        content = content.replace(/@(\w+)/g, '<a href="profile.php?username=$1" class="mention">@$1</a>');
        
        // Convert line breaks to HTML
        content = content.replace(/\n/g, '<br>');
        
        return content;
    }

    extractHashtags(content) {
        const matches = content.match(/#(\w+)/g);
        return matches ? matches.map(tag => tag.substring(1)) : [];
    }

    timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + 'd ago';
        if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + 'mo ago';
        return Math.floor(diffInSeconds / 31536000) + 'y ago';
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        // Insert at top of main content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.insertBefore(alert, mainContent.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    }

    showLoading(show) {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            if (show) {
                overlay.classList.add('show');
            } else {
                overlay.classList.remove('show');
            }
        }
    }

    isLoggedIn() {
        return document.body.classList.contains('logged-in') || 
               document.querySelector('.logout-btn') !== null;
    }

    getCurrentUserId() {
        // This would need to be set by the server-side code
        return window.currentUserId || null;
    }
}

// Global functions for HTML onclick handlers
function openPostModal() {
    if (window.app) {
        window.app.openPostModal();
    }
}

function closePostModal() {
    if (window.app) {
        window.app.closePostModal();
    }
}

function submitPost() {
    if (window.app) {
        window.app.submitPost();
    }
}

function refreshFeed() {
    if (window.app) {
        window.app.refreshFeed();
    }
}

function logout() {
    if (window.app) {
        window.app.logout();
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new TAItterApp();
});

// Add some CSS for dynamic elements
const style = document.createElement('style');
style.textContent = `
    .hashtag-item, .user-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .hashtag-item:last-child, .user-item:last-child {
        border-bottom: none;
    }
    
    .hashtag-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
    }
    
    .hashtag-link:hover {
        text-decoration: underline;
    }
    
    .post-count {
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    
    .user-item .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
    }
    
    .user-item .avatar {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }
    
    .user-item .user-details {
        flex: 1;
    }
    
    .user-item .username {
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .user-item .user-description {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
`;
document.head.appendChild(style);