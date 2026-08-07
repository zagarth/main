<?php
// Contact Form Modal Component

// Use centralized session manager
require_once __DIR__ . '/session_manager.php';

function renderContactModal() {
    // Session is already started by session_manager.php
    ?>
    <!-- Contact Form Modal -->
    <div id="contact-modal" class="contact-modal-overlay" style="display: none;">
        <div class="contact-modal-container">
            <div class="contact-modal-content">
                <button class="contact-modal-close" onclick="closeContactModal()" aria-label="Close contact form">
                    <span>&times;</span>
                </button>
                
                <div class="contact-modal-header">
                    <h2>Contact Us</h2>
                    <p>Get in touch with us about custom jewelry, quotes, or any questions you may have.</p>
                </div>
                
                <div class="contact-modal-body">
                    <?php
                    // Include and render the contact form
                    include_once 'contact_form.php';
                    renderContactForm('', '');
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Modal Styles -->
    <style>
    .contact-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(5px);
        z-index: 15000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow-y: auto;
    }

    .contact-modal-container {
        width: 100%;
        max-width: 900px;
        margin: auto;
        position: relative;
    }

    .contact-modal-content {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .contact-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.1);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 10001;
    }

    .contact-modal-close:hover {
        background: rgba(0, 0, 0, 0.2);
        transform: rotate(90deg);
    }

    .contact-modal-close span {
        font-size: 32px;
        line-height: 1;
        color: #333;
    }

    .contact-modal-header {
        padding: 40px 40px 20px 40px;
        text-align: center;
        border-bottom: 2px solid #f0f0f0;
    }

    .contact-modal-header h2 {
        color: #8B4513 !important;
        font-size: 2rem;
        margin-bottom: 10px;
        text-shadow: none !important;
    }

    .contact-modal-header p {
        color: #666 !important;
        font-size: 1rem;
        margin: 0;
    }

    .contact-modal-body {
        padding: 30px 40px 40px 40px;
    }

    /* Override form styles for modal */
    .contact-modal-body #formtable {
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }

    .contact-modal-body #formtable h2 {
        display: none !important; /* Hide duplicate heading */
    }

    .contact-modal-body .form-group label {
        color: #333 !important;
    }

    .contact-modal-body .form-group input,
    .contact-modal-body .form-group textarea,
    .contact-modal-body .form-group select {
        border: 2px solid #ddd !important;
        background: white !important;
        color: #333 !important;
    }

    .contact-modal-body .form-group input:focus,
    .contact-modal-body .form-group textarea:focus,
    .contact-modal-body .form-group select:focus {
        border-color: #FFD700 !important;
        outline: none !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .contact-modal-overlay {
            padding: 10px;
        }

        .contact-modal-header {
            padding: 30px 20px 15px 20px;
        }

        .contact-modal-header h2 {
            font-size: 1.5rem;
        }

        .contact-modal-body {
            padding: 20px;
        }

        .contact-modal-close {
            width: 35px;
            height: 35px;
            top: 10px;
            right: 10px;
        }

        .contact-modal-close span {
            font-size: 28px;
        }
    }
    </style>

    <!-- Contact Modal JavaScript -->
    <script>
    function openContactModal(prefilledMessage = '') {
        const modal = document.getElementById('contact-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            
            // Prefill message if provided
            if (prefilledMessage) {
                const messageField = document.getElementById('message');
                if (messageField) {
                    messageField.value = prefilledMessage;
                }
            }
        }
    }

    function openContactModalWithTracking(sourcePage, sourceSection, prefilledMessage = '') {
        // Send tracking data to server via AJAX
        fetch('/track_contact_source.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                source_page: sourcePage,
                source_section: sourceSection,
                page_url: window.location.href,
                timestamp: new Date().toISOString()
            })
        }).then(response => response.json())
          .then(data => {
              console.log('Contact source tracked:', data);
          })
          .catch(error => {
              console.error('Error tracking contact source:', error);
          });
        
        // Open the modal with optional prefilled message
        openContactModal(prefilledMessage);
    }

    function closeContactModal() {
        const modal = document.getElementById('contact-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
            
            // Clear success/error messages from URL
            const url = new URL(window.location);
            if (url.searchParams.has('success') || url.searchParams.has('error')) {
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        }
    }

    // Close modal when clicking outside the content
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('contact-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeContactModal();
                }
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeContactModal();
            }
        });
        
        // Auto-open modal if there's a success or error message in URL
        const urlHash = window.location.hash;
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlHash === '#contact-success' || urlHash === '#contact-error' || 
            urlParams.has('success') || urlParams.has('error')) {
            // Open the modal to show the message
            openContactModal();
            
            // Clear the hash from URL (but keep the query params for the message)
            if (urlHash) {
                history.replaceState(null, null, ' ');
            }
        }
    });
    </script>
    <?php
}
?>
