// TAItter - Complete Working JavaScript Application

class TAItterApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupPostModal();
        // Load sidebar data with working APIs
        if (this.isLoggedIn()) {
            setTimeout(() => {
                this.loadSidebarData();
            }, 500);
        }
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
                    charCount.style.color = '#e0245e';
                } else if (count > 120) {
                    charCount.style.color = '#ffad1f';
                } else {
                    charCount.style.color = '#aab8c2';
                }
            });
        }

        // Like user and follow hashtag buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.like-user-btn')) {
                e.preventDefault();
                this.handleLikeUser(e.target.closest('.like-user-btn'));
            }
            
            if (e.target.closest('.follow-hashtag-btn')) {
                e.preventDefault();
                this.handleFollowHashtags(e.target.closest('.follow-hashtag-btn'));
            }
        });
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
                            setTimeout(() => postContent.focus(), 100);
                        }
                    }
                });
            });
            observer.observe(modal, { attributes: true });
        }
    }

    async loadSidebarData() {
        if (!this.isLoggedIn()) return;

        // Load trending hashtags
        try {
            const hashtagsContainer = document.getElementById('trending-hashtags');
            if (hashtagsContainer) {
                const response = await fetch('api/hashtags.php?action=all&limit=5');
                if (response.ok) {
                    const text = await response.text();
                    if (text.trim().startsWith('{')) {
                        const data = JSON.parse(text);
                        if (data && data.hashtags) {
                            this.renderTrendingHashtags(data.hashtags);
                        }
                    }
                }
            }
        } catch (error) {
            console.log('Could not load hashtags');
        }

        // Load suggested users
        try {
            const usersContainer = document.getElementById('suggested-users');
            if (usersContainer) {
                const response = await fetch('api/users.php?action=search&q=');
                if (response.ok) {
                    const text = await response.text();
                    if (text.trim().startsWith('{')) {
                        const data = JSON.parse(text);
                        if (data && data.users) {
                            this.renderSuggestedUsers(data.users.slice(0, 5));
                        }
                    }
                }
            }
        } catch (error) {
            console.log('Could not load users');
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
                <a href="hashtag.php?tag=${encodeURIComponent(hashtag.tag)}" class="hashtag-link">
                    #${this.escapeHtml(hashtag.tag)}
                </a>
                <span class="post-count">${hashtag.post_count || 0} posts</span>
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

        // Filter out current user
        const currentUserId = this.getCurrentUserId();
        const filteredUsers = users.filter(user => user.id != currentUserId);

        const html = filteredUsers.map(user => `
            <div class="user-item">
                <div class="user-info">
                    <div class="avatar">${this.escapeHtml(user.username.charAt(0).toUpperCase())}</div>
                    <div class="user-details">
                        <a href="profile.php?username=${encodeURIComponent(user.username)}" class="username">@${this.escapeHtml(user.username)}</a>
                        <p class="user-description">${this.escapeHtml(user.description || 'No description')}</p>
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
        const charCount = document.getElementById('char-count');
        
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
        
        if (postContent) {
            postContent.value = '';
        }
        
        if (charCount) {
            charCount.textContent = '0';
            charCount.style.color = '#aab8c2';
        }
    }

    async submitPost() {
        const postContent = document.getElementById('post-content');
        if (!postContent) return;

        const content = postContent.value.trim();
        if (!content) {
            alert('Please enter some content for your post');
            return;
        }

        if (content.length > 144) {
            alert('Post content must be 144 characters or less');
            return;
        }

        this.showLoading(true);

        try {
            const response = await fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ content })
            });

            if (response.ok) {
                this.closePostModal();
                alert('Post created successfully!');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                const data = await response.json();
                alert(data.error || 'Failed to create post');
            }
        } catch (error) {
            console.log('Error creating post:', error);
            alert('Network error. Please try again.');
        } finally {
            this.showLoading(false);
        }
    }

    refreshFeed() {
        window.location.reload();
    }

    async handleLikeUser(button) {
        if (!this.isLoggedIn()) {
            alert('Please login to like users');
            return;
        }

        const userId = button.dataset.userId;
        if (!userId) return;

        const isLiked = button.querySelector('i').classList.contains('fas');
        
        // Optimistic UI update
        const icon = button.querySelector('i');
        if (isLiked) {
            icon.classList.remove('fas');
            icon.classList.add('far');
        } else {
            icon.classList.remove('far');
            icon.classList.add('fas');
        }

        try {
            const url = isLiked ? `api/users.php?id=${userId}` : 'api/users.php';
            const method = isLiked ? 'DELETE' : 'POST';
            const body = isLiked ? null : JSON.stringify({ user_id: parseInt(userId) });
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: body
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Like action successful:', data.message);
            } else {
                // Revert the UI change on error
                if (isLiked) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
                console.log('Like action failed');
            }
        } catch (error) {
            // Revert the UI change on error
            if (isLiked) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            console.log('Network error for like action:', error);
        }
    }

    async handleFollowHashtags(button) {
        if (!this.isLoggedIn()) {
            alert('Please login to follow hashtags');
            return;
        }

        const hashtags = JSON.parse(button.dataset.hashtags || '[]');
        if (hashtags.length === 0) {
            alert('No hashtags found in this post');
            return;
        }

        try {
            for (const hashtag of hashtags) {
                // First, get hashtag info
                const hashtagResponse = await fetch(`api/hashtags.php?action=search&q=${hashtag}`);
                if (hashtagResponse.ok) {
                    const text = await hashtagResponse.text();
                    if (text.trim().startsWith('{')) {
                        const hashtagData = JSON.parse(text);
                        
                        if (hashtagData && hashtagData.hashtags && hashtagData.hashtags.length > 0) {
                            const hashtagId = hashtagData.hashtags[0].id;
                            
                            // Follow the hashtag
                            await fetch('api/hashtags.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({ hashtag_id: hashtagId })
                            });
                        }
                    }
                }
            }
            
            alert(`Following ${hashtags.length} hashtag(s)!`);
        } catch (error) {
            console.log('Error following hashtags:', error);
            alert('Could not follow hashtags. Please try again.');
        }
    }

    async logout() {
        if (confirm('Are you sure you want to logout?')) {
            try {
                // Try POST request first
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ action: 'logout' })
                });
                
                if (response.ok) {
                    window.location.href = 'login.php';
                } else {
                    // Fallback: direct redirect
                    window.location.href = 'api/auth.php';
                }
            } catch (error) {
                console.log('Logout error:', error);
                // Fallback: direct redirect
                window.location.href = 'api/auth.php';
            }
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
               document.querySelector('.logout-btn') !== null ||
               window.currentUserId != null;
    }

    getCurrentUserId() {
        return window.currentUserId || null;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
    console.log('TAItter app initialized successfully');
});

// Add CSS for dynamic elements
if (!document.getElementById('dynamic-styles')) {
    const style = document.createElement('style');
    style.id = 'dynamic-styles';
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
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .user-item .user-details {
            flex: 1;
        }
        
        .user-item .username {
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .user-item .username:hover {
            color: var(--primary-color);
        }
        
        .user-item .user-description {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .text-muted {
            color: var(--text-muted);
        }
    `;
    document.head.appendChild(style);
}