    <footer>
        <div class="container text-center">
            <p class="mb-0">© <?= date('Y'); ?> Healthcare Center. <?= __('all_rights_reserved'); ?>.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/main.js'); ?>"></script>
    <?php if ($currentPage === 'index'): ?>
    <script>
        // Auto-refresh posts every 30 seconds
        (function() {
            let lastPostIds = [];
            const container = document.getElementById('postsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const countSpan = document.getElementById('postCount');
            const timeSpan = document.getElementById('lastPostUpdate');
            
            function getCurrentIds() {
                const items = document.querySelectorAll('[data-post-id]');
                return Array.from(items).map(item => item.getAttribute('data-post-id') || '');
            }
            
            lastPostIds = getCurrentIds();
            
            async function refreshPosts() {
                if (loadingSpinner) {
                    loadingSpinner.classList.remove('d-none');
                }
                
                try {
                    // Add cache-busting timestamp to prevent browser caching
                    const cacheBuster = '?t=' + Date.now();
                    const response = await fetch('<?= url("/api/posts.php"); ?>' + cacheBuster, {
                        method: 'GET',
                        cache: 'no-cache',
                        headers: {
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        }
                    });
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        // API already returns posts in correct order (sorted by database)
                        // Trust the API order and don't re-sort to maintain consistency
                        
                        // Compare current posts with new posts to detect any changes
                        const currentPosts = Array.from(document.querySelectorAll('[data-post-id]')).map(item => {
                            const id = item.getAttribute('data-post-id');
                            const title = item.querySelector('.post-title')?.textContent?.trim() || '';
                            const excerpt = item.querySelector('.post-excerpt')?.textContent?.trim() || '';
                            return { id, title, excerpt };
                        });
                        
                        const newIds = data.data.map(a => a.id || '');
                        const hasNewPosts = newIds.some(id => !lastPostIds.includes(id));
                        const orderChanged = JSON.stringify(newIds) !== JSON.stringify(lastPostIds);
                        
                        // Check if any existing posts were edited (title or excerpt changed)
                        const hasEdits = data.data.some(newPost => {
                            const currentPost = currentPosts.find(p => p.id === newPost.id);
                            if (!currentPost) return false; // New post, not an edit
                            // Check if title or excerpt changed (indicating an edit)
                            const titleChanged = currentPost.title !== (newPost.title || '').trim();
                            const excerptChanged = currentPost.excerpt !== (newPost.excerpt || '').trim();
                            return titleChanged || excerptChanged;
                        });
                        
                        // Always update to maintain consistency with API order
                        // API returns posts in correct order, so we should always update
                        updatePostsList(data.data);
                        lastPostIds = newIds;
                        
                        if (countSpan) {
                            countSpan.textContent = data.data.length;
                        }
                        if (timeSpan) {
                            const now = new Date();
                            timeSpan.textContent = now.toLocaleTimeString();
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing posts:', error);
                } finally {
                    if (loadingSpinner) {
                        loadingSpinner.classList.add('d-none');
                    }
                }
            }
            
            function updatePostsList(posts) {
                if (!container) return;
                
                if (posts.length === 0) {
                    container.innerHTML = '<div class="alert alert-info text-center"><p class="mb-0">No posts at this time. Check back soon for updates!</p></div>';
                    return;
                }
                
                // Don't re-sort - API already returns posts in correct order (matching database query)
                // Re-sorting can cause inconsistencies. Trust the API order.
                
                let html = '<div class="posts-list" id="postsList">';
                posts.forEach(function(post) {
                    const blogUrl = '<?= url("/public/blog.php"); ?>?post=' + encodeURIComponent(post.slug || '');
                    const dateStr = post.published_at || post.updated_at || '';
                    const date = dateStr ? new Date(dateStr) : new Date();
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric'
                    });
                    html += `
                        <article class="post-card" data-post-id="${post.id || ''}">
                            <div class="post-meta">
                                <i class="bi bi-calendar3 me-1"></i>
                                <time datetime="${dateStr}">${formattedDate}</time>
                            </div>
                            <h3 class="post-title">${escapeHtml(post.title || '')}</h3>
                            <p class="post-excerpt">${escapeHtml(post.excerpt || '')}</p>
                            <a href="${blogUrl}" class="post-link">
                                Read Story <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </article>
                    `;
                });
                html += '</div>';
                html += `
                    <div class="posts-footer">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Showing <span id="postCount">${posts.length}</span> post(s) - 
                            Last updated: <span id="lastPostUpdate">${new Date().toLocaleTimeString()}</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" id="loadingSpinner" role="status"></span>
                        </small>
                    </div>
                `;
                
                container.innerHTML = html;
                
                lastPostIds = getCurrentIds();
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Refresh every 5 seconds to catch edits quickly
            setInterval(refreshPosts, 5000);
            
            // Also refresh immediately on page focus (when user switches back to tab)
            window.addEventListener('focus', refreshPosts);
            
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    refreshPosts();
                }
            });
        })();
    </script>
    <?php endif; ?>
    <?php if ($currentPage === 'blog'): ?>
    <script>
        // Auto-refresh blog posts every 5 seconds
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentDate = urlParams.get('date') || '';
            const currentSlug = urlParams.get('post') || '';
            
            let lastPostsHash = '';
            let lastSelectedContent = '';
            
            // Get initial state - use specific IDs to avoid selecting Categories list
            const postsList = document.getElementById('recentPostsList');
            const articleContent = document.querySelector('.announcement-content');
            if (postsList) {
                lastPostsHash = postsList.innerHTML;
            }
            if (articleContent) {
                lastSelectedContent = articleContent.innerHTML;
            }
            
            async function refreshBlog() {
                try {
                    const params = new URLSearchParams();
                    if (currentDate) params.set('date', currentDate);
                    if (currentSlug) params.set('post', currentSlug);
                    params.set('t', Date.now()); // Cache buster
                    
                    const response = await fetch('<?= url("/api/blog.php"); ?>?' + params.toString(), {
                        method: 'GET',
                        cache: 'no-cache',
                        headers: {
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        }
                    });
                    const result = await response.json();
                    
                    if (result.success && result.data) {
                        updatePostsList(result.data.posts);
                        updateSelectedPost(result.data.selectedPost);
                        updateDateButtons(result.data.postDates);
                    }
                } catch (error) {
                    console.error('Error refreshing blog:', error);
                }
            }
            
            function updatePostsList(posts) {
                const container = document.getElementById('recentPostsList');
                if (!container) return;
                
                const linkParams = currentDate ? `date=${encodeURIComponent(currentDate)}&` : '';
                const selectedId = document.querySelector('.list-group-item.bg-primary')?.getAttribute('href')?.match(/post=([^&]+)/)?.[1] || currentSlug;
                
                let html = '';
                posts.forEach(function(post) {
                    const isActive = post.slug === decodeURIComponent(selectedId);
                    const postDateStr = post.published_at || post.created_at || '';
                    const postDate = postDateStr ? new Date(postDateStr) : new Date();
                    const formattedDate = postDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    html += `
                        <a class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1 ${isActive ? 'bg-primary text-white' : ''}" 
                           href="<?= url('/public/blog.php'); ?>?${linkParams}post=${encodeURIComponent(post.slug)}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1 me-2">
                                    <strong class="d-block mb-1">${escapeHtml(post.title)}</strong>
                                    <p class="small mb-1 lh-sm ${isActive ? 'text-white-50' : 'text-muted'}">${escapeHtml(post.excerpt)}</p>
                                    <small class="${isActive ? 'text-white-50' : 'text-muted'}">
                                        <i class="bi bi-clock me-1"></i>${formattedDate}
                                    </small>
                                </div>
                                <i class="bi bi-chevron-right ${isActive ? 'text-white-50' : 'text-muted'}"></i>
                            </div>
                        </a>
                    `;
                });
                
                if (html !== lastPostsHash) {
                    container.innerHTML = html;
                    lastPostsHash = html;
                }
            }
            
            function updateSelectedPost(post) {
                if (!post) return;
                
                const titleEl = document.querySelector('article h1.fw-bold');
                const contentEl = document.querySelector('.announcement-content');
                const dateEl = document.querySelector('article .text-muted');
                
                if (titleEl && titleEl.textContent !== post.title) {
                    titleEl.textContent = post.title;
                }
                
                if (contentEl && post.content !== lastSelectedContent) {
                    contentEl.innerHTML = post.content;
                    lastSelectedContent = post.content;
                }
                
                if (dateEl) {
                    const dateStr = post.published_at || post.created_at || '';
                    const date = dateStr ? new Date(dateStr) : new Date();
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const archivedBadge = post.archived_at ? '<span class="badge bg-secondary me-2">Archived</span>' : '';
                    let dateHtml = archivedBadge + 'Published: ' + formattedDate;
                    if (post.archived_at) {
                        const archivedDate = new Date(post.archived_at);
                        dateHtml += '<br>Archived: ' + archivedDate.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                    dateEl.innerHTML = dateHtml;
                }
            }
            
            function updateDateButtons(dates) {
                const container = document.querySelector('.d-flex.flex-wrap.gap-1');
                if (!container || !dates || dates.length === 0) return;
                
                let html = '';
                dates.slice(0, 10).forEach(function(dateStr) {
                    const date = new Date(dateStr + 'T00:00:00');
                    const formatted = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const isSelected = currentDate === dateStr;
                    html += `
                        <a href="<?= url('/public/blog.php'); ?>?date=${encodeURIComponent(dateStr)}" 
                           class="btn btn-sm ${isSelected ? 'btn-primary' : 'btn-outline-primary'}">
                            ${formatted}
                        </a>
                    `;
                });
                container.innerHTML = html;
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }
            
            // Refresh every 5 seconds
            setInterval(refreshBlog, 5000);
            
            // Refresh on tab focus
            window.addEventListener('focus', refreshBlog);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    refreshBlog();
                }
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>

