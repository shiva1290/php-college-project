// Comment moderation functions
async function approveComment(commentId) {
    try {
        const response = await fetch('/includes/post_interactions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache'
            },
            body: `action=approve_comment&comment_id=${encodeURIComponent(commentId)}`
        });

        const data = await response.json();
        
        if (response.ok && data.success) {
            const commentCard = document.querySelector(`.pending-comment-card[data-comment-id="${commentId}"]`);
            if (commentCard) {
                commentCard.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    commentCard.remove();
                    
                    // Check if there are no more comments
                    const remainingComments = document.querySelectorAll('.pending-comment-card');
                    if (remainingComments.length === 0) {
                        const commentsList = document.querySelector('.comments-list');
                        if (commentsList) {
                            commentsList.innerHTML = '<div class="no-comments">No pending comments</div>';
                        }
                    }
                }, 300);
            }
            showToast('Comment approved successfully', 'success');
            
            // Refresh posts to show approved comment in the main feed
            setTimeout(() => refreshPosts(), 1000);
        } else {
            const errorMessage = data.message || 'Failed to approve comment';
            console.error('Server response:', data);
            showToast(errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error while approving comment', 'error');
    }
}

async function rejectComment(commentId) {
    try {
        const response = await fetch('/includes/post_interactions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject_comment&comment_id=${encodeURIComponent(commentId)}`
        });

        const data = await response.json();
        
        if (response.ok && data.success) {
            const commentCard = document.querySelector(`.pending-comment-card[data-comment-id="${commentId}"]`);
            if (commentCard) {
                commentCard.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    commentCard.remove();
                    
                    // Check if there are no more comments
                    const remainingComments = document.querySelectorAll('.pending-comment-card');
                    if (remainingComments.length === 0) {
                        const commentsList = document.querySelector('.comments-list');
                        if (commentsList) {
                            commentsList.innerHTML = '<div class="no-comments">No pending comments</div>';
                        }
                    }
                }, 300);
            }
            showToast('Comment rejected successfully', 'success');
        } else {
            const errorMessage = data.message || 'Failed to reject comment';
            console.error('Server response:', data);
            showToast(errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error while rejecting comment', 'error');
    }
}

// Add CSS for animations and empty state
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        0% {
            opacity: 1;
            transform: translateX(0);
        }
        100% {
            opacity: 0;
            transform: translateX(-100%);
        }
    }

    .no-comments {
        text-align: center;
        padding: 20px;
        color: var(--text-muted);
        font-size: 0.9em;
    }
`;
document.head.appendChild(style);

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Trigger reflow
    toast.offsetHeight;
    
    // Add visible class
    toast.classList.add('visible');
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.documentElement.setAttribute('data-theme',
                document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'
            );
            localStorage.setItem('theme', document.documentElement.getAttribute('data-theme'));
        });
    }

    // Post interactions
    document.querySelectorAll('.interaction-btn').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            if (!this.dataset.postId) return;

            // If this is a comment button, handle it differently
            if (this.classList.contains('comment-btn')) {
                handleCommentClick(this);
                return;
            }

            const postId = this.dataset.postId;
            const type = this.dataset.type;
            const isUpvote = this.classList.contains('upvote-btn');
            
            // Add loading state
            this.classList.add('loading');
            const countSpan = this.querySelector('.interaction-count');
            const originalCount = parseInt(countSpan.textContent) || 0;
            const wasActive = this.classList.contains('active');

            try {
                const response = await fetch('/includes/post_interactions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${isUpvote ? 'upvote' : 'react'}&post_id=${encodeURIComponent(postId)}${type ? `&type=${encodeURIComponent(type)}` : ''}`
                });

                const data = await response.json();
                
                if (response.ok && data.success !== false) {
                    // Toggle active state with animation
                    this.classList.toggle('active');
                    
                    // Update count with animation
                    const newCount = wasActive ? originalCount - 1 : originalCount + 1;
                    countSpan.style.transform = 'scale(1.2)';
                    countSpan.textContent = newCount;
                    setTimeout(() => {
                        countSpan.style.transform = 'scale(1)';
                    }, 200);

                    // Show feedback toast
                    showToast(wasActive ? 'Interaction removed' : 'Interaction added', 'success');
                } else {
                    const errorMessage = data.message || 'Failed to update interaction';
                    showToast(errorMessage, 'error');
                    console.error('Server response:', data);
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            } finally {
                this.classList.remove('loading');
            }
        });
    });

    // Handle comment button click
    function handleCommentClick(button) {
        const postId = button.dataset.postId;
        const postCard = button.closest('.post-card');
        const commentsSection = postCard.querySelector('.comments');
        
        // Check if comment form already exists
        let commentForm = commentsSection.querySelector('.comment-form');
        if (commentForm) {
            // If form exists, just focus it
            commentForm.querySelector('textarea').focus();
            return;
        }

        // Create comment form
        commentForm = document.createElement('div');
        commentForm.className = 'comment-form';
        commentForm.innerHTML = `
            <form>
                <textarea placeholder="Write your comment..." maxlength="500" required></textarea>
                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="button-text">Submit</span>
                        <span class="loading-indicator"></span>
                    </button>
                </div>
            </form>
        `;

        // Add form to comments section
        if (commentsSection.firstChild) {
            commentsSection.insertBefore(commentForm, commentsSection.firstChild);
        } else {
            commentsSection.appendChild(commentForm);
        }

        // Focus textarea
        const textarea = commentForm.querySelector('textarea');
        textarea.focus();

        // Handle form submission
        commentForm.querySelector('form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = commentForm.querySelector('button[type="submit"]');
            const buttonText = submitButton.querySelector('.button-text');
            const loadingIndicator = submitButton.querySelector('.loading-indicator');

            // Disable submit button and show loading state
            submitButton.disabled = true;
            buttonText.style.opacity = '0';
            loadingIndicator.style.display = 'block';

            try {
                const response = await fetch('/includes/submit_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${encodeURIComponent(postId)}&content=${encodeURIComponent(textarea.value)}`
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showToast('Comment submitted for approval', 'success');
                    commentForm.remove();
                } else {
                    const errorMessage = data.message || 'Failed to submit comment';
                    showToast(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred while submitting comment', 'error');
            } finally {
                // Reset button state
                submitButton.disabled = false;
                buttonText.style.opacity = '1';
                loadingIndicator.style.display = 'none';
            }
        });
    }

    // Delete post
    document.querySelectorAll('.delete-post').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this post?')) return;

            const postId = this.dataset.postId;
            const postCard = this.closest('.post-card');

            try {
                const response = await fetch('includes/delete_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${postId}`
                });

                if (response.ok) {
                    postCard.style.opacity = '0';
                    setTimeout(() => {
                        postCard.remove();
                    }, 300);
                    showToast('Post deleted successfully', 'success');
                } else {
                    showToast('Failed to delete post', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            }
        });
    });

    // Handle post form submission
    const postForm = document.getElementById('postForm');
    if (postForm) {
        const textarea = postForm.querySelector('textarea');
        const charCount = postForm.querySelector('.char-count span');
        const maxLength = parseInt(textarea.getAttribute('maxlength'));

        // Update character count
        textarea.addEventListener('input', () => {
            const length = textarea.value.length;
            charCount.textContent = length;
            charCount.parentElement.classList.toggle('limit', length >= maxLength - 100);
        });

        postForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sharing...';

            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    this.reset();
                    charCount.textContent = '0';
                    charCount.parentElement.classList.remove('limit');
                    window.location.reload();
                } else {
                    alert(result.error || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating your post');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    }

    // Handle reading history
    const postCards = document.querySelectorAll('.post-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const post = entry.target;
                const postId = post.dataset.postId;
                
                // Mark as read after 5 seconds of visibility
                setTimeout(async () => {
                    if (entry.isIntersecting) {
                        try {
                            const response = await fetch('/includes/mark_read.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `post_id=${postId}`
                            });
                            
                            if (response.ok) {
                                post.classList.add('read');
                                observer.unobserve(post);
                            }
                        } catch (error) {
                            console.error('Error marking post as read:', error);
                        }
                    }
                }, 5000);
            }
        });
    }, { threshold: 0.5 });

    postCards.forEach(post => observer.observe(post));

    // Handle comment form submissions
    document.querySelectorAll('.comment-form').forEach(form => {
        const textarea = form.querySelector('textarea');
        const charCount = form.querySelector('.char-count span');
        const maxLength = parseInt(textarea.getAttribute('maxlength'));

        // Update character count
        textarea.addEventListener('input', () => {
            const length = textarea.value.length;
            charCount.textContent = length;
            charCount.parentElement.classList.toggle('limit', length >= maxLength - 50);
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const buttonText = submitButton.querySelector('.button-text');
            const loadingIndicator = submitButton.querySelector('.loading-indicator');
            const originalButtonText = buttonText.textContent;
            
            submitButton.disabled = true;
            buttonText.style.display = 'none';
            loadingIndicator.style.display = 'block';

            try {
                const formData = new FormData(this);
                console.log('Submitting comment:', {
                    postId: formData.get('post_id'),
                    content: formData.get('content')
                });

                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);

                if (result.success) {
                    // Clear the form
                    this.reset();
                    charCount.textContent = '0';
                    charCount.parentElement.classList.remove('limit');
                    
                    // Add the new comment to the UI as pending
                    const commentsSection = this.closest('.post-card').querySelector('.comments');
                    const newComment = document.createElement('div');
                    newComment.className = 'comment pending';
                    newComment.innerHTML = `
                        <div class="comment-author">
                            <div class="user-icon tiny" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=${result.comment.icon_seed}')"></div>
                            <span>${result.comment.username}</span>
                        </div>
                        <div class="comment-content">
                            ${result.comment.content.replace(/\n/g, '<br>')}
                        </div>
                        <div class="comment-meta">
                            <span class="pending-badge">Pending Approval</span>
                            <time datetime="${result.comment.created_at}">
                                Just now
                            </time>
                        </div>
                        <div class="pending-notice">
                            <svg viewBox="0 0 24 24" class="pending-icon">
                                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                            </svg>
                            <p>Your comment is awaiting moderator approval. This helps ensure high-quality discussions.</p>
                        </div>
                    `;
                    commentsSection.insertBefore(newComment, commentsSection.firstChild);

                    // Show a toast notification
                    const toast = document.createElement('div');
                    toast.className = 'toast pending-toast';
                    toast.innerHTML = `
                        <div class="toast-content">
                            <svg viewBox="0 0 24 24" class="pending-icon">
                                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                            </svg>
                            <p>${result.message}</p>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.classList.add('show');
                        setTimeout(() => {
                            toast.classList.remove('show');
                            setTimeout(() => toast.remove(), 300);
                        }, 5000);
                    }, 100);
                } else {
                    throw new Error(result.error || 'Failed to submit comment');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while submitting your comment');
            } finally {
                submitButton.disabled = false;
                buttonText.style.display = 'block';
                buttonText.textContent = originalButtonText;
                loadingIndicator.style.display = 'none';
            }
        });
    });

    // Format timestamps
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) {
            return 'just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: 'numeric'
            });
        }
    }

    // Update all timestamps
    function updateTimestamps() {
        document.querySelectorAll('time').forEach(timeElement => {
            const timestamp = timeElement.getAttribute('datetime');
            timeElement.textContent = formatTimestamp(timestamp);
        });
    }

    // Update timestamps initially and every minute
    updateTimestamps();
    setInterval(updateTimestamps, 60000);
    
    // Start auto-refresh for real-time updates
    startAutoRefresh();
    
    // Stop auto-refresh when page is hidden (tab switching)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    });
    
    // Initial setup of event listeners
    attachPostInteractionListeners();
    attachCommentFormListeners();

    // Auto-refresh functionality
    let refreshInterval;

    function startAutoRefresh() {
        // Refresh posts every 30 seconds
        refreshInterval = setInterval(() => {
            refreshPosts();
        }, 30000);
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }

    async function refreshPosts() {
        try {
            const response = await fetch(window.location.href + '?ajax=1', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (response.ok) {
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update posts container
                const newPostsContainer = doc.querySelector('.posts-container');
                const currentPostsContainer = document.querySelector('.posts-container');
                
                if (newPostsContainer && currentPostsContainer) {
                    // Check if content has changed
                    if (newPostsContainer.innerHTML !== currentPostsContainer.innerHTML) {
                        currentPostsContainer.innerHTML = newPostsContainer.innerHTML;
                        
                        // Re-attach event listeners for new content
                        attachPostInteractionListeners();
                        attachCommentFormListeners();
                        
                        // Show subtle notification
                        showToast('New content available', 'info');
                    }
                }
                
                // Update sidebar stats if they exist
                const newStats = doc.querySelector('.user-stats');
                const currentStats = document.querySelector('.user-stats');
                if (newStats && currentStats) {
                    currentStats.innerHTML = newStats.innerHTML;
                }
                
                // Update recent activity
                const newActivity = doc.querySelector('.recent-activity');
                const currentActivity = document.querySelector('.recent-activity');
                if (newActivity && currentActivity) {
                    currentActivity.innerHTML = newActivity.innerHTML;
                }
            }
        } catch (error) {
            console.log('Auto-refresh failed:', error);
            // Don't show error to user for background refresh
        }
    }

    function attachPostInteractionListeners() {
        document.querySelectorAll('.interaction-btn').forEach(button => {
            // Remove existing listeners by cloning the element
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', handlePostInteraction);
        });
    }

    function attachCommentFormListeners() {
        document.querySelectorAll('.comment-form form').forEach(form => {
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            
            newForm.addEventListener('submit', handleCommentSubmit);
        });
    }

    async function handlePostInteraction(e) {
        e.preventDefault();
        if (!this.dataset.postId) return;

        // If this is a comment button, handle it differently
        if (this.classList.contains('comment-btn')) {
            handleCommentClick(this);
            return;
        }

        const postId = this.dataset.postId;
        const type = this.dataset.type;
        const isUpvote = this.classList.contains('upvote-btn');
        
        // Add loading state
        this.classList.add('loading');
        const countSpan = this.querySelector('.interaction-count');
        const originalCount = parseInt(countSpan.textContent) || 0;
        const wasActive = this.classList.contains('active');

        try {
            const response = await fetch('/includes/post_interactions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Cache-Control': 'no-cache'
                },
                body: `action=${isUpvote ? 'upvote' : 'react'}&post_id=${encodeURIComponent(postId)}${type ? `&type=${encodeURIComponent(type)}` : ''}`
            });

            const data = await response.json();
            
            if (response.ok && data.success !== false) {
                // Toggle active state with animation
                this.classList.toggle('active');
                
                // Update count with animation
                const newCount = wasActive ? originalCount - 1 : originalCount + 1;
                countSpan.style.transform = 'scale(1.2)';
                countSpan.textContent = newCount;
                setTimeout(() => {
                    countSpan.style.transform = 'scale(1)';
                }, 200);

                // Show feedback toast
                showToast(wasActive ? 'Interaction removed' : 'Interaction added', 'success');
                
                // Refresh posts after interaction to show updated counts
                setTimeout(() => refreshPosts(), 1000);
            } else {
                const errorMessage = data.message || 'Failed to update interaction';
                showToast(errorMessage, 'error');
                console.error('Server response:', data);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('An error occurred', 'error');
        } finally {
            this.classList.remove('loading');
        }
    }

    async function handleCommentSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const textarea = this.querySelector('textarea');
        
        if (!textarea.value.trim()) {
            showToast('Please enter a comment', 'error');
            return;
        }
        
        // Add loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-indicator"></span> Submitting...';
        
        try {
            const response = await fetch('/includes/submit_comment.php', {
                method: 'POST',
                headers: {
                    'Cache-Control': 'no-cache'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                textarea.value = '';
                showToast(data.message || 'Comment submitted for review', 'success');
                
                // Refresh posts to show the new comment status
                setTimeout(() => refreshPosts(), 1000);
            } else {
                showToast(data.message || 'Failed to submit comment', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Failed to submit comment', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Comment';
        }
    }
}); 