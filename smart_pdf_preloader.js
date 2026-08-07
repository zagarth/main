/**
 * Simple PDF Cache Manager
 * Lightweight version for testing
 */
class SmartPDFPreloader {
    constructor() {
        console.log('SmartPDFPreloader constructor called');
        this.cacheConsent = localStorage.getItem('pdf_cache_consent');
        this.init();
    }
    
    init() {
        console.log('SmartPDFPreloader init() called, consent:', this.cacheConsent);
        
        if (this.cacheConsent === null) {
            this.showConsentDialog();
        } else if (this.cacheConsent === 'accepted') {
            this.showToast('Cache enabled - PDFs will load faster!', 'success');
        }
    }
    
    showConsentDialog() {
        console.log('Showing consent dialog');
        
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.7); z-index: 10000; 
            display: flex; align-items: center; justify-content: center;
        `;
        
        const dialog = document.createElement('div');
        dialog.style.cssText = `
            background: white; border-radius: 15px; padding: 30px; max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 3px solid #FFD700;
            text-align: center; font-family: Arial, sans-serif;
        `;
        
        dialog.innerHTML = `
            <h3 style="color: #FFD700; margin-bottom: 20px;">💾 Enable Fast PDF Loading?</h3>
            <p style="margin-bottom: 20px; color: #333;">
                Cache PDFs locally for instant loading and smart preloading of popular pages.
            </p>
            <div style="background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <div>✨ Instant loading of viewed PDFs</div>
                <div>🚀 Smart preloading of popular pages</div>
                <div>📱 Optimized for your device</div>
            </div>
            <p style="font-size: 0.9em; color: #666; margin: 15px 0;">
                Uses up to 25-75MB local storage. No personal data collected.
            </p>
            <button id="acceptBtn" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin: 5px; cursor: pointer; font-weight: bold;">✅ Enable Caching</button>
            <button id="declineBtn" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin: 5px; cursor: pointer;">❌ No Thanks</button>
        `;
        
        modal.appendChild(dialog);
        document.body.appendChild(modal);
        
        document.getElementById('acceptBtn').onclick = () => {
            localStorage.setItem('pdf_cache_consent', 'accepted');
            this.cacheConsent = 'accepted';
            document.body.removeChild(modal);
            this.showToast('✅ Smart caching enabled!', 'success');
        };
        
        document.getElementById('declineBtn').onclick = () => {
            localStorage.setItem('pdf_cache_consent', 'declined');
            this.cacheConsent = 'declined';
            document.body.removeChild(modal);
            this.showToast('Caching disabled', 'info');
        };
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
        
        toast.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; 
            background: ${bgColor}; color: white; padding: 15px 20px;
            border-radius: 8px; font-weight: bold; z-index: 10001;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transform: translateX(400px); transition: transform 0.3s ease;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.style.transform = 'translateX(0)', 100);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }
}

// Make the class available globally
if (typeof window !== 'undefined') {
    window.SmartPDFPreloader = SmartPDFPreloader;
    console.log('SmartPDFPreloader class registered globally');
}