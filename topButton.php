<?php
// Top Button component for Cadman Mfg website
function renderTopButton() {
    echo '
    <!-- Back to Top Button -->
    <div id="topBtn">
        <a href="#" onclick="scrollToTop(); return false;" aria-label="Back to top">
            <span>↑</span>
        </a>
    </div>
    
    <!-- Top Button Styles -->
    <style>
    #topBtn {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 999;
        border: none;
        outline: none;
        background: linear-gradient(145deg, rgba(0, 102, 204, 0.9), rgba(0, 82, 163, 0.9));
        color: white;
        cursor: pointer;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.2);
    }
    
    #topBtn:hover {
        background: linear-gradient(145deg, rgba(0, 102, 204, 1), rgba(0, 82, 163, 1));
        transform: translateY(-2px) scale(1.1);
        box-shadow: 0 6px 20px rgba(0,0,0,0.4);
    }
    
    #topBtn a {
        color: white;
        text-decoration: none;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
        border-radius: 50%;
    }
    
    #topBtn a span {
        transition: transform 0.3s ease;
    }
    
    #topBtn:hover a span {
        transform: translateY(-2px);
    }
    
    /* Mobile adjustments */
    @media (max-width: 768px) {
        #topBtn {
            bottom: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
        }
        
        #topBtn a {
            font-size: 16px;
        }
    }
    </style>
    
    <!-- Top Button JavaScript -->
    <script>
    // Top button functionality with smooth scroll
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
        return false;
    }
    
    // Show/hide top button based on scroll position
    function handleTopButtonVisibility() {
        const topBtn = document.getElementById("topBtn");
        if (topBtn) {
            if (window.pageYOffset > 300) {
                topBtn.style.display = "block";
            } else {
                topBtn.style.display = "none";
            }
        }
    }
    
    // Throttled scroll handler for better performance
    let ticking = false;
    function updateTopButton() {
        handleTopButtonVisibility();
        ticking = false;
    }
    
    function requestTopButtonUpdate() {
        if (!ticking) {
            requestAnimationFrame(updateTopButton);
            ticking = true;
        }
    }
    
    // Add scroll listener
    window.addEventListener("scroll", requestTopButtonUpdate, { passive: true });
    
    // Make function globally available
    if (typeof window !== "undefined") {
        window.scrollToTop = scrollToTop;
    }
    
    // Initialize on page load (defer-safe: DOMContentLoaded fires after deferred scripts)
    document.addEventListener("DOMContentLoaded", function() {
        handleTopButtonVisibility();
    });
    </script>';
}
?>
