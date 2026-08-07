/**
 * Image Gallery and Rotation Functionality
 * 
 * Provides image rotation, auto-rotation, and gallery management
 * for collection pages with variant images
 */

// Fallback for closest() method for older browsers
function findClosestContainer(element, className) {
    // Modern browsers
    if (element.closest) {
        return element.closest('.' + className);
    }
    
    // Fallback for older browsers
    while (element && element !== document) {
        if (element.classList && element.classList.contains(className)) {
            return element;
        }
        element = element.parentNode;
    }
    return null;
}

// Image gallery rotation management
let autoRotationIntervals = new Map();

// Image rotation functionality - with on-demand variant loading
function rotateImage(container) {
    const variants = JSON.parse(container.dataset.variants || '[]');
    const categoryPath = container.dataset.categoryPath;
    
    if (variants.length <= 1) return;
    
    let currentVariant = parseInt(container.dataset.currentVariant) || 0;
    currentVariant = (currentVariant + 1) % variants.length;
    
    const image = container.querySelector('.rotating-image');
    const nextVariant = variants[currentVariant];
    
    // Construct image paths - prioritize thumbnails for faster loading
    let thumbPath = categoryPath.replace('bands_php/images/', 'bands_php/thumbs/images/') + '/' + nextVariant;
    let fullPath = categoryPath + '/' + nextVariant;
    
    // Load variant image on-demand (only when user rotates)
    const variantImg = new Image();
    
    variantImg.onload = function() {
        // Variant loaded successfully, update the display
        updateImageWithAnimation(image, thumbPath);
        container.dataset.currentVariant = currentVariant;
    };
    
    variantImg.onerror = function() {
        // Thumbnail failed, try full image
        const fallbackImg = new Image();
        fallbackImg.onload = function() {
            updateImageWithAnimation(image, fullPath);
            container.dataset.currentVariant = currentVariant;
        };
        fallbackImg.onerror = function() {
            console.warn('Failed to load image variant:', nextVariant);
            // Don't update currentVariant if image fails to load
        };
        fallbackImg.src = fullPath;
    };
    
    // Start loading the variant image only when needed
    variantImg.src = thumbPath;
}

function updateImageWithAnimation(imageElement, newSrc) {
    const container = imageElement.closest('.rotating-image-container');
    
    // Show loading state
    container.classList.add('loading');
    imageElement.style.opacity = '0';
    
    // Create a new image to preload
    const tempImg = new Image();
    
    tempImg.onload = function() {
        // Image loaded successfully
        container.classList.remove('loading');
        imageElement.src = newSrc;
        imageElement.classList.add('image-rotating');
        imageElement.style.opacity = '1';
        
        setTimeout(() => {
            imageElement.classList.remove('image-rotating');
        }, 400);
    };
    
    tempImg.onerror = function() {
        // Failed to load
        container.classList.remove('loading');
        console.warn('Failed to load image:', newSrc);
        imageElement.style.opacity = '1'; // Show previous image
    };
    
    // Start loading
    tempImg.src = newSrc;
}

// Auto-rotation functionality
function startAutoRotation(container) {
    const variants = JSON.parse(container.dataset.variants || '[]');
    if (variants.length <= 1) return;
    
    // Stop any existing rotation first
    stopAutoRotation(container);
    
    const intervalId = setInterval(() => {
        rotateImage(container);
    }, 3000); // Rotate every 3 seconds
    
    autoRotationIntervals.set(container, intervalId);
}

function stopAutoRotation(container) {
    const intervalId = autoRotationIntervals.get(container);
    if (intervalId) {
        clearInterval(intervalId);
        autoRotationIntervals.delete(container);
    }
}

function stopAllAutoRotation() {
    autoRotationIntervals.forEach((intervalId, container) => {
        clearInterval(intervalId);
    });
    autoRotationIntervals.clear();
}

// Enhanced image gallery initialization
function initializeImageGallery() {
    // Add event listeners for manual rotation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.rotating-image-container')) {
            const container = e.target.closest('.rotating-image-container');
            rotateImage(container);
            
            // Stop auto-rotation when user manually rotates
            stopAutoRotation(container);
        }
    });
    
    // Add hover effects for auto-rotation with fallback for older browsers
    document.addEventListener('mouseenter', function(e) {
        const container = findClosestContainer(e.target, 'rotating-image-container');
        if (container) {
            startAutoRotation(container);
        }
    }, true);
    
    document.addEventListener('mouseleave', function(e) {
        const container = findClosestContainer(e.target, 'rotating-image-container');
        if (container) {
            stopAutoRotation(container);
        }
    }, true);
    
    // Pause auto-rotation when page is not visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAllAutoRotation();
        }
    });
    
    console.log('Image gallery initialized with rotation functionality');
}

// Lazy loading for images
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => imageObserver.observe(img));
    }
}

// Initialize all gallery functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeImageGallery();
    initializeLazyLoading();
});
