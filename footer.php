<?php
/**
 * Footer Component for Cadman Manufacturing
 * Reusable footer with navigation links, contact info, and copyright
 */

function renderFooter($currentPage = '') {
?>
<!-- Footer -->
<footer style="background: linear-gradient(135deg, #1a1a1a, #2d2d2d); color: white; margin-top: 40px; padding: 30px 0 15px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Main Footer Content -->
        <div class="footer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 30px;">
            
            <!-- Company Info -->
            <div class="footer-section">
                <h3 style="color: #FFD700; margin-bottom: 15px; font-size: 1.3em; border-bottom: 2px solid #FFD700; padding-bottom: 8px; display: inline-block;">Cadman Manufacturing</h3>
                <p style="line-height: 1.5; margin-bottom: 12px; color: #ccc; font-size: 0.95em;">
                    Crafting exceptional custom jewelry with precision and artistry since our founding.
                </p>
                <div class="contact-info" style="margin-bottom: 12px;">
                    <p style="margin: 3px 0; color: #ccc; font-size: 0.9em;">
                        <strong style="color: #FFD700;">📧</strong> 
                        <a href="mailto:info@cadmanmfg.com" style="color: #87CEEB; text-decoration: none;">info@cadmanmfg.com</a>
                    </p>
                    <p style="margin: 3px 0; color: #ccc; font-size: 0.9em;">
                        <strong style="color: #FFD700;">📞</strong> 
                        <span style="color: #87CEEB;">(519) 688-2121</span>
                    </p>
                    <p style="margin: 3px 0; color: #ccc; font-size: 0.9em;">
                        <strong style="color: #FFD700;">📞</strong> 
                        <span style="color: #87CEEB;">1-800-265-5790</span>
                    </p>
                    <p style="margin: 3px 0; color: #ccc; font-size: 0.9em;">
                        <strong style="color: #FFD700;">📍</strong> 
                        <span style="color: #87CEEB;">Courtland, ON</span>
                    </p>
                </div>
            </div>
            
            <!-- Main Collections -->
            <div class="footer-section">
                <h3 style="color: #FFD700; margin-bottom: 15px; font-size: 1.3em; border-bottom: 2px solid #FFD700; padding-bottom: 8px; display: inline-block;">Collections</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                    <a href="/Engagement.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        💎 Engagement
                    </a>
                    <a href="/Bands.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        💍 Wedding Bands
                    </a>
                    <a href="/Family.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        👨‍👩‍👧‍👦 Family
                    </a>
                    <a href="/Signet.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        🛡️ Signet
                    </a>
                    <a href="/Accessories.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        ✨ Accessories
                    </a>
                    <a href="/Ladys_Stoneset.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        💍 Stoneset
                    </a>
                </div>
            </div>
            
            <!-- Specialty Collections -->
            <div class="footer-section">
                <h3 style="color: #FFD700; margin-bottom: 15px; font-size: 1.3em; border-bottom: 2px solid #FFD700; padding-bottom: 8px; display: inline-block;">Specialties</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                    <a href="/School.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        🎓 School & Shoulders
                    </a>
                    <a href="/Corp.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        🏢 Corporate
                    </a>
                    <a href="/Frontline_Workers.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        🚑 Frontline Workers
                    </a>
                    <a href="/catalog_direct.php" style="color: #ccc; text-decoration: none; transition: color 0.3s ease; font-size: 0.9em; padding: 2px 0;" 
                       onmouseover="this.style.color='#FFD700'" onmouseout="this.style.color='#ccc'">
                        📖 Full Catalog
                    </a>
                </div>
            </div>                 
        </div>
        
        <!-- Bottom Footer Bar -->
        <div class="footer-bottom" style="border-top: 1px solid #444; padding-top: 15px; text-align: center;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px;">
                
                <!-- Copyright -->
                <div style="color: #999; font-size: 0.85em;">
                    © <?php echo date('Y'); ?> <strong style="color: #FFD700;">Cadman Manufacturing</strong>. All rights reserved.
                </div>
                
            </div>
        </div>
    </div>
</footer>

<!-- Footer Mobile Responsive Styles -->
<style>
/* Footer Responsive Design */
@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr !important;
        gap: 20px !important;
        margin-bottom: 20px !important;
    }
    
    .footer-section h3 {
        font-size: 1.2em !important;
        margin-bottom: 12px !important;
        padding-bottom: 6px !important;
    }
    
    .footer-section div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
        gap: 6px !important;
    }
    
    .contact-info p {
        font-size: 0.85em !important;
        margin: 2px 0 !important;
    }
    
    .footer-bottom {
        padding-top: 12px !important;
    }
    
    .footer-bottom > div {
        flex-direction: column !important;
        gap: 10px !important;
        text-align: center !important;
    }
    
    .footer-bottom div[style*="gap: 15px"] {
        gap: 12px !important;
        justify-content: center !important;
    }
}

@media (max-width: 480px) {
    footer {
        margin-top: 30px !important;
        padding: 20px 0 10px !important;
    }
    
    footer > div {
        padding: 0 15px !important;
    }
    
    .footer-grid {
        gap: 15px !important;
        margin-bottom: 15px !important;
    }
    
    .footer-section {
        text-align: center;
    }
    
    .footer-section h3 {
        font-size: 1.1em !important;
        margin-bottom: 10px !important;
    }
    
    .footer-section p,
    .footer-section a {
        font-size: 0.8em !important;
    }
    
    .contact-info {
        margin-bottom: 8px !important;
    }
    
    .footer-bottom {
        padding-top: 10px !important;
    }
    
    .footer-bottom div,
    .footer-bottom a {
        font-size: 0.75em !important;
    }
}

/* Hover effects optimization */
@media (hover: hover) {
    .footer-section a:hover {
        transform: translateX(2px);
        transition: all 0.2s ease;
    }
}

/* Touch device optimizations */
@media (hover: none) {
    .footer-section a {
        padding: 8px 4px !important;
        display: block !important;
    }
}
</style>

<?php
}

// If the file is called directly (not included), render the default footer
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "<!DOCTYPE html><html><head><title>Footer Preview</title></head><body>";
    renderFooter();
    echo "</body></html>";
}
?>
