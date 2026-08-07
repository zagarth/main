/**
 * Carousel API Authentication Helper
 * Provides secure authentication for main site access to admin carousel API
 */

class CarouselApiAuth {
    constructor() {
        this.apiEndpoint = '/admin/api/carousel_auth.php';
        this.tokenCache = null;
        this.tokenExpiry = null;
    }

    /**
     * Generate SHA-256 hash using Web Crypto API
     */
    async sha256(message) {
        const msgBuffer = new TextEncoder().encode(message);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Generate HMAC-SHA256 signature
     */
    async hmacSha256(key, data) {
        const encoder = new TextEncoder();
        const cryptoKey = await crypto.subtle.importKey(
            'raw',
            encoder.encode(key),
            { name: 'HMAC', hash: 'SHA-256' },
            false,
            ['sign']
        );
        
        const signature = await crypto.subtle.sign('HMAC', cryptoKey, encoder.encode(data));
        const hashArray = Array.from(new Uint8Array(signature));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Generate authentication token
     */
    async generateToken(timestamp = null) {
        if (timestamp === null) {
            timestamp = Math.floor(Date.now() / 1000);
        }

        // This should match the server-side secret
        // For production, this would be loaded from a secure configuration
        const apiSecret = 'CadmanCSRF2025SecretKey!AdminPortal';
        const siteId = window.location.hostname;
        const data = `${siteId}|${timestamp}|carousel_api`;

        try {
            const token = await this.hmacSha256(apiSecret, data);
            return { token, timestamp };
        } catch (error) {
            console.error('Failed to generate authentication token:', error);
            throw new Error('Authentication token generation failed');
        }
    }

    /**
     * Check if cached token is still valid
     */
    isCachedTokenValid() {
        if (!this.tokenCache || !this.tokenExpiry) {
            return false;
        }

        // Check if token expires within next 30 seconds
        const currentTime = Math.floor(Date.now() / 1000);
        return this.tokenExpiry > (currentTime + 30);
    }

    /**
     * Get valid authentication headers
     */
    async getAuthHeaders() {
        // Use cached token if still valid
        if (this.isCachedTokenValid()) {
            return {
                'X-Auth-Token': this.tokenCache.token,
                'X-Auth-Timestamp': this.tokenCache.timestamp.toString()
            };
        }

        // Generate new token
        try {
            const { token, timestamp } = await this.generateToken();
            
            // Cache the token for future use
            this.tokenCache = { token, timestamp };
            this.tokenExpiry = timestamp + 240; // Valid for 4 minutes (server allows 5)
            
            return {
                'X-Auth-Token': token,
                'X-Auth-Timestamp': timestamp.toString()
            };
        } catch (error) {
            console.error('Failed to generate authentication headers:', error);
            throw error;
        }
    }

    /**
     * Fetch carousel data with authentication
     */
    async fetchCarouselData() {
        try {
            const headers = await this.getAuthHeaders();
            
            const response = await fetch(this.apiEndpoint, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    ...headers
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(`API request failed: ${response.status} - ${errorData.error || response.statusText}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(`API error: ${data.error || 'Unknown error'}`);
            }

            return data.items;
        } catch (error) {
            console.error('Carousel API request failed:', error);
            
            // Clear cached token on authentication errors
            if (error.message.includes('401') || error.message.includes('authentication')) {
                this.tokenCache = null;
                this.tokenExpiry = null;
            }
            
            throw error;
        }
    }
}

// Global instance for easy access
window.carouselAuth = new CarouselApiAuth();

/**
 * Legacy function for compatibility with existing carousel loading code
 */
async function loadCarouselImages() {
    try {
        const items = await window.carouselAuth.fetchCarouselData();
        
        if (!items || items.length === 0) {
            console.warn('No carousel items received from API');
            return;
        }

        updateCarouselDisplay(items);
    } catch (error) {
        console.error('Failed to load carousel images:', error);
        
        // Show fallback or error state
        const carouselContainer = document.querySelector('.carousel-container');
        if (carouselContainer) {
            carouselContainer.innerHTML = '<div class="carousel-error">Unable to load carousel images</div>';
        }
    }
}

/**
 * Update carousel display with fetched items
 */
function updateCarouselDisplay(items) {
    const carouselContainer = document.querySelector('.carousel-container');
    if (!carouselContainer) {
        console.warn('Carousel container not found');
        return;
    }

    // Clear existing content
    carouselContainer.innerHTML = '';

    // Create carousel items
    items.forEach((item, index) => {
        const carouselItem = document.createElement('div');
        carouselItem.className = 'carousel-item';
        if (index === 0) carouselItem.classList.add('active');

        const img = document.createElement('img');
        img.src = item.src; // Use 'src' property from API response
        img.alt = item.name || 'Jewelry item';
        img.loading = 'lazy';
        
        // Add error handling for individual images
        img.onerror = function() {
            console.warn(`Failed to load image: ${this.src}`);
            this.style.display = 'none';
        };

        carouselItem.appendChild(img);
        carouselContainer.appendChild(carouselItem);
    });

    console.log(`Carousel updated with ${items.length} items`);
}

// Auto-load carousel when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadCarouselImages);
} else {
    loadCarouselImages();
}