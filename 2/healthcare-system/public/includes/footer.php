    <footer>
        <div class="container text-center">
            <p class="mb-0">© <?= date('Y'); ?> Healthcare Center. All rights reserved.</p>
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
                return Array.from(items).map(item => parseInt(item.getAttribute('data-post-id')));
            }
            
            lastPostIds = getCurrentIds();
            
            async function refreshPosts() {
                if (loadingSpinner) {
                    loadingSpinner.classList.remove('d-none');
                }
                
                try {
                    const response = await fetch('<?= url("/api/posts.php"); ?>');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const newIds = data.data.map(a => a.id || 0);
                        const hasNew = newIds.some(id => !lastPostIds.includes(id));
                        
                        if (hasNew || JSON.stringify(newIds) !== JSON.stringify(lastPostIds)) {
                            updatePostsList(data.data);
                            lastPostIds = newIds;
                        }
                        
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
                    container.innerHTML = '<div class="alert alert-info text-center"><p class="mb-0">No posts at this time. Check back soon for new stories!</p></div>';
                    return;
                }
                
                let html = '<div class="list-group announcement-feed" id="postsList">';
                posts.forEach(function(post) {
                    const blogUrl = '<?= url("/public/blog.php"); ?>?post=' + encodeURIComponent(post.slug || '');
                    const date = new Date(post.published_at || post.updated_at);
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    html += `
                        <div class="list-group-item mb-3 border rounded shadow-sm" data-post-id="${post.id || ''}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    ${formattedDate}
                                </small>
                            </div>
                            <div class="announcement-content">
                                <h5 class="fw-bold">${post.title}</h5>
                                <p>${post.excerpt}</p>
                                <a class="btn btn-sm btn-outline-primary" href="${blogUrl}">Read story</a>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                html += `
                    <div class="text-center mt-3">
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
            
            setInterval(refreshPosts, 30000);
            
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    refreshPosts();
                }
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>

