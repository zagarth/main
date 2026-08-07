# Interactive Jewelry Configurator - Blue Nile Style System

## Vision: Interactive Product Customization

Similar to Blue Nile's configurator where users can:
- Select metal type (Silver, Gold 10K/14K/18K, Platinum)
- Choose finish (Polish, Brushed, Satin, Hammered)
- Pick ring size (4-15, half sizes)
- Select stone options (Diamond quality, birthstones, etc.)
- Add engraving (character count, font style)
- Choose width (for bands: 4mm, 6mm, 8mm, 10mm)
- See price updates in real-time
- View visual previews (if available)
- Add customization notes

## Technical Architecture

### JSON Configuration Structure
```json
{
  "product_options": {
    "bands": {
      "metal_type": {
        "label": "Metal Type",
        "type": "select",
        "required": true,
        "default": "sterling_silver",
        "options": [
          {
            "id": "sterling_silver",
            "label": "Sterling Silver",
            "price_modifier": 0,
            "image_suffix": "SS"
          },
          {
            "id": "10k_gold",
            "label": "10K Gold (Yellow/White/Rose)",
            "price_modifier": 250,
            "sub_options": ["yellow", "white", "rose"]
          },
          {
            "id": "14k_gold",
            "label": "14K Gold (Yellow/White/Rose)",
            "price_modifier": 400,
            "sub_options": ["yellow", "white", "rose"]
          },
          {
            "id": "18k_gold",
            "label": "18K Gold (Yellow/White/Rose)",
            "price_modifier": 650,
            "sub_options": ["yellow", "white", "rose"]
          },
          {
            "id": "platinum",
            "label": "Platinum 950",
            "price_modifier": 800
          }
        ]
      },
      "width": {
        "label": "Band Width",
        "type": "select",
        "required": true,
        "default": "6mm",
        "options": [
          {"id": "4mm", "label": "4mm (Delicate)", "price_modifier": -50},
          {"id": "6mm", "label": "6mm (Standard)", "price_modifier": 0},
          {"id": "8mm", "label": "8mm (Bold)", "price_modifier": 75},
          {"id": "10mm", "label": "10mm (Statement)", "price_modifier": 150}
        ]
      },
      "finish": {
        "label": "Surface Finish",
        "type": "select",
        "required": true,
        "default": "polish",
        "options": [
          {"id": "polish", "label": "High Polish", "price_modifier": 0},
          {"id": "brushed", "label": "Brushed", "price_modifier": 25},
          {"id": "satin", "label": "Satin", "price_modifier": 25},
          {"id": "hammered", "label": "Hammered", "price_modifier": 50},
          {"id": "antiqued", "label": "Antiqued", "price_modifier": 40}
        ]
      },
      "profile": {
        "label": "Ring Profile",
        "type": "select",
        "required": true,
        "default": "comfort_fit",
        "options": [
          {"id": "flat", "label": "Flat Band", "price_modifier": 0},
          {"id": "comfort_fit", "label": "Comfort Fit (Recommended)", "price_modifier": 30},
          {"id": "domed", "label": "Domed", "price_modifier": 25}
        ]
      },
      "size": {
        "label": "Ring Size",
        "type": "select",
        "required": true,
        "default": "7",
        "help_text": "Not sure of your size? <a href='#size-guide'>View Size Guide</a>",
        "options": "4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,10.5,11,11.5,12,12.5,13,13.5,14,14.5,15"
      },
      "engraving": {
        "label": "Inside Band Engraving",
        "type": "text",
        "required": false,
        "max_length": 25,
        "price_modifier": 35,
        "placeholder": "e.g., John & Jane 10.09.25",
        "help_text": "Up to 25 characters including spaces"
      },
      "gift_box": {
        "label": "Presentation",
        "type": "select",
        "required": true,
        "default": "standard",
        "options": [
          {"id": "standard", "label": "Standard Box", "price_modifier": 0},
          {"id": "premium", "label": "Premium Gift Box", "price_modifier": 15},
          {"id": "luxury", "label": "Luxury Wooden Box", "price_modifier": 45}
        ]
      }
    },
    "engagement": {
      "metal_type": {
        "label": "Metal Type",
        "type": "select",
        "required": true,
        "options": [
          {"id": "14k_gold", "label": "14K Gold", "price_modifier": 0, "sub_options": ["yellow", "white", "rose"]},
          {"id": "18k_gold", "label": "18K Gold", "price_modifier": 250, "sub_options": ["yellow", "white", "rose"]},
          {"id": "platinum", "label": "Platinum 950", "price_modifier": 400}
        ]
      },
      "center_stone": {
        "label": "Center Stone",
        "type": "select",
        "required": true,
        "options": [
          {"id": "customer_supplied", "label": "I will supply the stone", "price_modifier": 0},
          {"id": "cadman_source", "label": "Have Cadman source the stone", "price_modifier": 0, "requires_consultation": true}
        ]
      },
      "stone_quality": {
        "label": "Diamond Quality (if Cadman sources)",
        "type": "select",
        "required": false,
        "depends_on": "center_stone=cadman_source",
        "options": [
          {"id": "good", "label": "Good (SI2, I-J)", "price_per_carat": 3000},
          {"id": "better", "label": "Better (SI1, H)", "price_per_carat": 4500},
          {"id": "best", "label": "Best (VS2, G)", "price_per_carat": 6000},
          {"id": "premium", "label": "Premium (VS1, F)", "price_per_carat": 7500},
          {"id": "exceptional", "label": "Exceptional (VVS2, E)", "price_per_carat": 9500}
        ]
      },
      "carat_weight": {
        "label": "Carat Weight (if Cadman sources)",
        "type": "select",
        "required": false,
        "depends_on": "center_stone=cadman_source",
        "options": "0.25,0.33,0.50,0.75,1.00,1.25,1.50,1.75,2.00,2.50,3.00"
      },
      "setting_style": {
        "label": "Setting Style",
        "type": "select",
        "required": true,
        "options": [
          {"id": "prong", "label": "Prong (Classic)", "price_modifier": 0},
          {"id": "bezel", "label": "Bezel (Modern)", "price_modifier": 150},
          {"id": "channel", "label": "Channel Set", "price_modifier": 200}
        ]
      },
      "side_stones": {
        "label": "Side Accents",
        "type": "select",
        "required": false,
        "options": [
          {"id": "none", "label": "No side stones", "price_modifier": 0},
          {"id": "small_diamonds", "label": "Small diamond accents", "price_modifier": 250},
          {"id": "pave", "label": "Pavé band", "price_modifier": 450}
        ]
      },
      "size": {
        "label": "Ring Size",
        "type": "select",
        "required": true,
        "options": "4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10"
      }
    },
    "family": {
      "metal_type": {
        "label": "Metal Type",
        "type": "select",
        "required": true,
        "options": [
          {"id": "sterling_silver", "label": "Sterling Silver", "price_modifier": 0},
          {"id": "10k_gold", "label": "10K Gold", "price_modifier": 200},
          {"id": "14k_gold", "label": "14K Gold", "price_modifier": 350}
        ]
      },
      "birthstones": {
        "label": "Birthstone Selection",
        "type": "multi_select",
        "required": false,
        "max_selections": 6,
        "price_per_stone": 45,
        "options": [
          {"id": "january", "label": "January (Garnet)", "color": "#8B0000"},
          {"id": "february", "label": "February (Amethyst)", "color": "#9966CC"},
          {"id": "march", "label": "March (Aquamarine)", "color": "#7FFFD4"},
          {"id": "april", "label": "April (Diamond)", "color": "#FFFFFF"},
          {"id": "may", "label": "May (Emerald)", "color": "#50C878"},
          {"id": "june", "label": "June (Pearl)", "color": "#F0EAD6"},
          {"id": "july", "label": "July (Ruby)", "color": "#E0115F"},
          {"id": "august", "label": "August (Peridot)", "color": "#9ACD32"},
          {"id": "september", "label": "September (Sapphire)", "color": "#0F52BA"},
          {"id": "october", "label": "October (Opal)", "color": "#FFB6C1"},
          {"id": "november", "label": "November (Topaz)", "color": "#FFD700"},
          {"id": "december", "label": "December (Turquoise)", "color": "#40E0D0"}
        ]
      },
      "personalization": {
        "label": "Engraving/Personalization",
        "type": "textarea",
        "required": false,
        "max_length": 50,
        "price_modifier": 40,
        "placeholder": "Names, dates, or special message"
      },
      "size": {
        "label": "Ring Size",
        "type": "select",
        "required": true,
        "options": "4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,10.5,11,11.5,12"
      }
    },
    "corp": {
      "metal_type": {
        "label": "Metal Type",
        "type": "select",
        "required": true,
        "options": [
          {"id": "sterling_silver", "label": "Sterling Silver", "price_modifier": 0},
          {"id": "10k_gold", "label": "10K Gold", "price_modifier": 300},
          {"id": "14k_gold", "label": "14K Gold", "price_modifier": 450}
        ]
      },
      "logo_customization": {
        "label": "Logo/Emblem Options",
        "type": "select",
        "required": true,
        "options": [
          {"id": "standard", "label": "Standard emblem", "price_modifier": 0},
          {"id": "custom_logo", "label": "Custom company logo", "price_modifier": 200, "requires_upload": true}
        ]
      },
      "enamel_color": {
        "label": "Enamel Color (if applicable)",
        "type": "color_picker",
        "required": false,
        "price_modifier": 60,
        "presets": ["#000000", "#FFFFFF", "#FF0000", "#0000FF", "#FFD700", "#008000"]
      },
      "quantity": {
        "label": "Order Quantity",
        "type": "select",
        "required": true,
        "bulk_discount": true,
        "options": [
          {"id": "1-9", "label": "1-9 pieces", "discount": 0},
          {"id": "10-24", "label": "10-24 pieces", "discount": 10},
          {"id": "25-49", "label": "25-49 pieces", "discount": 15},
          {"id": "50-99", "label": "50-99 pieces", "discount": 20},
          {"id": "100+", "label": "100+ pieces", "discount": 25, "requires_quote": true}
        ]
      }
    }
  },
  "price_calculation": {
    "base_prices": {
      "bands": 450,
      "engagement": 1200,
      "family": 250,
      "corp": 400,
      "accessories": 185
    },
    "tax_rate": 0.13,
    "shipping": {
      "standard": 15,
      "express": 35,
      "international": 75
    }
  }
}
```

## UI Components Needed

### 1. Configurator Interface (HTML/CSS/JavaScript)
```html
<div class="product-configurator">
    <!-- Left side: Visual preview -->
    <div class="configurator-preview">
        <img id="product-image" src="..." alt="Product preview">
        <div class="image-selector">
            <!-- Thumbnails for different angles -->
        </div>
    </div>
    
    <!-- Right side: Options -->
    <div class="configurator-options">
        <h2>Customize Your Ring</h2>
        
        <!-- Dynamic option sections rendered from JSON -->
        <div class="option-section" id="metal-type-section">
            <label>Metal Type</label>
            <select name="metal_type" id="metal_type">
                <!-- Populated from JSON -->
            </select>
        </div>
        
        <!-- Price summary (live updating) -->
        <div class="price-summary">
            <div class="base-price">
                <span>Base Price:</span>
                <span id="base-price">$450.00</span>
            </div>
            <div class="modifications">
                <!-- Dynamic price modifiers -->
            </div>
            <div class="total-price">
                <span>Total:</span>
                <span id="total-price">$450.00</span>
            </div>
        </div>
        
        <!-- Action buttons -->
        <button class="add-to-cart-btn" id="add-to-cart">Add to Cart</button>
        <button class="request-quote-btn" id="request-quote">Request Custom Quote</button>
    </div>
</div>
```

### 2. JavaScript Configurator Engine
```javascript
class JewelryConfigurator {
    constructor(itemId, collection, configUrl) {
        this.itemId = itemId;
        this.collection = collection;
        this.config = null;
        this.selections = {};
        this.basePrice = 0;
        this.loadConfig(configUrl);
    }
    
    async loadConfig(url) {
        const response = await fetch(url);
        this.config = await response.json();
        this.init();
    }
    
    init() {
        this.basePrice = this.config.price_calculation.base_prices[this.collection];
        this.renderOptions();
        this.bindEvents();
        this.updatePrice();
    }
    
    renderOptions() {
        const options = this.config.product_options[this.collection];
        // Render each option section
    }
    
    updatePrice() {
        let total = this.basePrice;
        let modifications = [];
        
        // Calculate price based on selections
        for (const [key, value] of Object.entries(this.selections)) {
            const modifier = this.getOptionModifier(key, value);
            if (modifier > 0) {
                total += modifier;
                modifications.push({name: key, amount: modifier});
            }
        }
        
        // Update UI
        document.getElementById('total-price').textContent = `$${total.toFixed(2)}`;
        this.renderModifications(modifications);
    }
    
    addToCart() {
        const cartItem = {
            itemId: this.itemId,
            collection: this.collection,
            customizations: this.selections,
            price: this.calculateTotal(),
            image: this.getCurrentImage()
        };
        
        // Add to cart system
        window.addItemToCart(cartItem);
    }
    
    requestQuote() {
        // Build detailed description
        const message = this.buildQuoteMessage();
        
        // Open contact modal with prefilled data
        openContactModalWithTracking(
            'Product Configurator',
            `Item: ${this.itemId}`,
            message
        );
    }
    
    buildQuoteMessage() {
        let msg = `I would like a quote for ${this.itemId} with the following customizations:\n\n`;
        
        for (const [key, value] of Object.entries(this.selections)) {
            msg += `${this.formatOptionName(key)}: ${value}\n`;
        }
        
        msg += `\nEstimated Price: $${this.calculateTotal().toFixed(2)}`;
        return msg;
    }
}
```

## Files to Create

1. **`product_configurator.json`** - Master configuration
2. **`configurator.js`** - JavaScript configurator engine
3. **`configurator.css`** - Styling for configurator UI
4. **`unified_detail.php`** - Modified to include configurator

## Implementation Benefits

### For Customers:
✅ Interactive, engaging shopping experience
✅ See exactly what they're ordering
✅ Real-time price updates
✅ No surprises at checkout
✅ Similar to high-end retailers (Blue Nile, James Allen)

### For Business:
✅ Reduce quote requests for standard options
✅ Capture exact customer requirements
✅ Higher conversion rates
✅ Better data on popular options
✅ Reduced order errors

### For Operations:
✅ Clear specifications in cart/emails
✅ Less back-and-forth communication
✅ Standardized option sets
✅ Easy to update pricing
✅ Track popular configurations

## Next Steps

1. **Phase 1: Core JSON Config** (Week 1)
   - Create product_configurator.json
   - Define all options for bands collection
   - Test JSON loading

2. **Phase 2: Basic Configurator** (Week 2)
   - Build JavaScript engine
   - Render options from JSON
   - Implement price calculation

3. **Phase 3: UI/UX** (Week 3)
   - Style configurator interface
   - Add visual feedback
   - Mobile responsive design

4. **Phase 4: Integration** (Week 4)
   - Connect to cart system
   - Update contact form integration
   - Email customization details

5. **Phase 5: Expansion** (Week 5+)
   - Add remaining collections
   - Add image switching based on options
   - Add size guide modal
   - Add "Save Configuration" feature

**Ready to proceed?** Should I start with creating the JSON configuration file for the bands collection as a proof-of-concept?
