/**
 * AR12 Pricing Calculator - JavaScript Implementation
 * Replicates COBOL AR12 pricing logic from Cadman Manufacturing
 * 
 * This module calculates product pricing based on:
 * - Material costs
 * - Labor hours and rates
 * - Metal content (gold, sterling)
 * - Stone costs
 * - Bill of Materials (component parts)
 * - Markup percentages
 * - Sales tax
 */

class PricingCalculator {
    constructor() {
        // System settings (from SY01.DAT - will need to export this)
        this.settings = {
            goldPrice: 0,        // SY-GOLD-PRCE (price per troy ounce)
            labourRate: 0,       // SY-LABOUR-RATE ($ per hour)
            sterlingGF: 0,       // SY-STERLING-GF (sterling gold factor)
            marketMarkup: 0      // SY-MRKT-MKUP (market markup %)
        };
        
        // Will be loaded from CSV files
        this.inventory = new Map();     // IC data - base inventory
        this.pricing = new Map();       // IP data - pricing records
        this.billOfMaterials = new Map(); // BM data - component lists
    }
    
    /**
     * Load system settings from SY export
     */
    loadSystemSettings(syData) {
        this.settings.goldPrice = parseFloat(syData.goldPrice) || 0;
        this.settings.labourRate = parseFloat(syData.labourRate) || 0;
        this.settings.sterlingGF = parseFloat(syData.sterlingGF) || 0;
        this.settings.marketMarkup = parseFloat(syData.marketMarkup) || 0;
    }
    
    /**
     * Calculate gold cost
     * Formula from AR12 lines 411-418:
     * - Convert oz price to gram price
     * - Apply karat multiplier
     */
    calculateGoldCost(goldGrams, karatType) {
        if (goldGrams === 0) return 0;

        karatType = this.normalizeKarat(karatType);
        
        // Apply karat purity factor to get pure gold weight
        // 10K = 10/24 = 41.67%, 14K = 14/24 = 58.33%, 18K = 18/24 = 75%
        let pureGoldGrams = goldGrams;
        if (karatType === '10K') {
            pureGoldGrams *= (10/24);
        } else if (karatType === '14K') {
            pureGoldGrams *= (14/24);
        } else if (karatType === '18K') {
            pureGoldGrams *= (18/24);
        } else if (karatType !== '24K') {
            pureGoldGrams *= (10/24);
        }
        
        // Price of gold per gram (1 troy oz = 31.1035 grams)
        let pricePerGram = this.settings.goldPrice / 31.1035;
        
        // Calculate cost based on pure gold weight
        let goldCost = pricePerGram * pureGoldGrams;
        
        return this.round(goldCost);
    }

    /**
     * Normalize metal labels to the base karat used for pricing.
     */
    normalizeKarat(karatType) {
        karatType = (karatType || '').toString().trim().toUpperCase();

        if (!karatType) {
            return '10K';
        }

        if (karatType === '24K') {
            return '24K';
        }

        const match = karatType.match(/^(10|14|18)K?(?:TT|W|Y)?$/);
        if (match) {
            return `${match[1]}K`;
        }

        const bareMatch = karatType.match(/^(10|14|18)$/);
        if (bareMatch) {
            return `${bareMatch[1]}K`;
        }

        return '10K';
    }
    
    /**
     * Calculate sterling silver cost
     * Formula from AR12 line 423
     */
    calculateSterlingCost(sterlingGrams) {
        if (sterlingGrams === 0) return 0;
        
        // Formula: grams * sterling_GF * 0.03215076
        return this.round(sterlingGrams * this.settings.sterlingGF * 0.03215076);
    }
    
    /**
     * Calculate labor cost
     * Formula from AR12 line 429
     */
    calculateLaborCost(laborHours) {
        return this.round(this.settings.labourRate * laborHours);
    }
    
    /**
     * Calculate total cost of item
     * Formula from AR12 lines 429-431
     */
    calculateTotalCost(item) {
        const laborCost = this.calculateLaborCost(item.laborHours || 0);
        const goldCost = this.calculateGoldCost(item.goldGrams || 0, item.metalType);
        const sterlingCost = this.calculateSterlingCost(item.sterlingGrams || 0);
        
        const totalCost = 
            (item.materialCost || 0) +
            laborCost +
            goldCost +
            sterlingCost +
            (item.stoneCost || 0) +
            (item.starCost || 0) +
            (item.stoneSettingCost || 0);
        
        return this.round(totalCost);
    }
    
    /**
     * Calculate Bill of Materials cost
     * Logic from AR12 lines 619-634
     * 
     * Reads all components for an item and calculates total cost
     */
    calculateBOMCost(itemCode) {
        const components = this.billOfMaterials.get(itemCode) || [];
        
        let bmCosts = {
            goldCost: 0,
            sterlingCost: 0,
            materialCost: 0,
            stoneCost: 0,
            laborCost: 0,
            stoneSettingCost: 0,
            starCost: 0
        };
        
        // Process each component
        for (const component of components) {
            const part = this.inventory.get(component.partNumber);
            if (!part) continue;
            
            const componentCost = this.round(component.quantity * part.cost);
            
            // Categorize cost by material type (logic from AR12 lines 623-629)
            if (['10K', '14K', '18K'].includes(component.metalType)) {
                bmCosts.goldCost += componentCost;
            } else if (component.metalType === 'STER') {
                bmCosts.sterlingCost += componentCost;
            } else if (component.metalType === 'STONE') {
                bmCosts.stoneCost += componentCost;
            } else {
                bmCosts.materialCost += componentCost;
            }
        }
        
        // Add non-BOM costs from item itself
        const item = this.pricing.get(itemCode);
        if (item) {
            bmCosts.laborCost = item.laborCost || 0;
            bmCosts.stoneSettingCost = item.stoneSettingCost || 0;
            bmCosts.starCost = item.starCost || 0;
        }
        
        // Total BOM cost (AR12 lines 631-633)
        bmCosts.total = 
            bmCosts.goldCost +
            bmCosts.sterlingCost +
            bmCosts.materialCost +
            bmCosts.stoneCost +
            bmCosts.laborCost +
            bmCosts.stoneSettingCost +
            bmCosts.starCost;
        
        return bmCosts;
    }
    
    /**
     * Calculate selling price with markups and tax
     * Formula from AR12 lines 436-440
     */
    calculateSellingPrice(cost, itemMarkup, salesTax, isSample = false) {
        if (isSample) return 0; // Samples have no selling price
        
        let price = cost;
        
        // Apply item markup
        price = price * (1 + (itemMarkup * 0.01));
        
        // Apply market markup
        price = price * (1 + (this.settings.marketMarkup * 0.01));
        
        // Apply sales tax
        price = price * (1 + (salesTax * 0.01));
        
        // Round to nearest quarter (25¢)
        // Logic from AR12 lines 441-445
        price = this.roundToQuarter(price);
        
        return price;
    }
    
    /**
     * Round to nearest quarter dollar ($0.25)
     * AR12 logic: if cents > 75 → round up, >50 → 75¢, >25 → 50¢, else 25¢
     */
    roundToQuarter(amount) {
        // Work in 0.001 cent precision (100000x) to avoid rounding errors
        const hundredThousandths = Math.round(amount * 100000);
        const dollars = Math.floor(hundredThousandths / 100000);
        const fractionalPart = hundredThousandths % 100000;
        
        // Convert to cents with high precision (100x = cents, but we have 100000x)
        const preciseCents = fractionalPart / 1000;  // Now in cents with 3 decimal precision
        
        // OE27 COBOL rounding logic: >75 → $1, >50 → .75, >25 → .50, else → .25
        if (preciseCents > 75) {
            return dollars + 1.00;
        } else if (preciseCents > 50) {
            return dollars + 0.75;
        } else if (preciseCents > 25) {
            return dollars + 0.50;
        } else {
            return dollars + 0.25;
        }
    }
    
    /**
     * Round to 2 decimal places
     */
    round(value) {
        return Math.round(value * 100) / 100;
    }
    
    /**
     * Complete pricing calculation for an item
     * Replicates full AR12 flow
     */
    calculateItemPricing(itemCode) {
        const item = this.pricing.get(itemCode);
        if (!item) {
            throw new Error(`Item ${itemCode} not found in pricing database`);
        }
        
        // Check if item has Bill of Materials
        const hasBOM = this.billOfMaterials.has(itemCode);
        
        let result;
        
        if (hasBOM) {
            // Calculate from BOM
            const bmCosts = this.calculateBOMCost(itemCode);
            result = {
                itemCode: itemCode,
                description: item.description,
                method: 'Bill of Materials',
                breakdown: {
                    goldCost: bmCosts.goldCost,
                    sterlingCost: bmCosts.sterlingCost,
                    materialCost: bmCosts.materialCost,
                    stoneCost: bmCosts.stoneCost,
                    laborCost: bmCosts.laborCost,
                    stoneSettingCost: bmCosts.stoneSettingCost,
                    starCost: bmCosts.starCost
                },
                totalCost: bmCosts.total
            };
        } else {
            // Calculate from item data
            const goldCost = this.calculateGoldCost(item.goldGrams, item.metalType);
            const sterlingCost = this.calculateSterlingCost(item.sterlingGrams);
            const laborCost = this.calculateLaborCost(item.laborHours);
            
            result = {
                itemCode: itemCode,
                description: item.description,
                method: 'Direct Calculation',
                breakdown: {
                    materialCost: item.materialCost || 0,
                    laborCost: laborCost,
                    goldCost: goldCost,
                    sterlingCost: sterlingCost,
                    stoneCost: item.stoneCost || 0,
                    starCost: item.starCost || 0,
                    stoneSettingCost: item.stoneSettingCost || 0
                },
                totalCost: this.calculateTotalCost(item)
            };
        }
        
        // Calculate selling price
        result.sellingPrice = this.calculateSellingPrice(
            result.totalCost,
            item.markup || 50,
            item.salesTax || 0,
            item.isSample
        );
        
        result.profit = result.sellingPrice - result.totalCost;
        result.profitMargin = result.totalCost > 0 
            ? ((result.profit / result.totalCost) * 100).toFixed(2) + '%'
            : '0%';
        
        return result;
    }
    
    /**
     * Load inventory data from IC CSV export
     */
    loadInventory(csvData) {
        // Parse IC-EXP01.csv
        const lines = csvData.trim().split('\n');
        const headers = lines[0].split('|');
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split('|');
            const item = {
                partNumber: values[0].trim(),
                description: values[1].trim(),
                class: values[2].trim(),
                cost: parseFloat(values[3]) || 0,
                materialCost: parseFloat(values[4]) || 0,
                metalHi: values[5].trim(),
                metalLo: values[6].trim(),
                group: values[7].trim(),
                goldGrams: parseFloat(values[8]) || 0,
                goldCost: parseFloat(values[9]) || 0,
                sterlingGrams: parseFloat(values[10]) || 0,
                sterlingCost: parseFloat(values[11]) || 0
            };
            this.inventory.set(item.partNumber, item);
        }
    }
    
    /**
     * Load pricing data from IP CSV export
     */
    loadPricing(csvData) {
        // Parse IP-EXP01.csv
        const lines = csvData.trim().split('\n');
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split('|');
            const item = {
                itemCode: values[0].trim(),
                description: values[1].trim(),
                price: parseFloat(values[2]) || 0,
                cost: parseFloat(values[3]) || 0,
                materialCost: parseFloat(values[4]) || 0,
                laborCost: parseFloat(values[5]) || 0,
                metalHi: values[6].trim(),
                metalLo: values[7].trim(),
                goldGrams: parseFloat(values[8]) || 0,
                goldCost: parseFloat(values[9]) || 0,
                sterlingGrams: parseFloat(values[10]) || 0,
                sterlingCost: parseFloat(values[11]) || 0,
                stoneCost: parseFloat(values[12]) || 0,
                starCost: parseFloat(values[13]) || 0,
                stoneSettingCost: parseFloat(values[14]) || 0,
                // Sales history months 1-12 in positions 15-26
                salesHistory: [
                    parseInt(values[15]) || 0, parseInt(values[16]) || 0,
                    parseInt(values[17]) || 0, parseInt(values[18]) || 0,
                    parseInt(values[19]) || 0, parseInt(values[20]) || 0,
                    parseInt(values[21]) || 0, parseInt(values[22]) || 0,
                    parseInt(values[23]) || 0, parseInt(values[24]) || 0,
                    parseInt(values[25]) || 0, parseInt(values[26]) || 0
                ],
                markup: parseFloat(values[27]) || 50,
                salesTax: parseFloat(values[28]) || 0,
                info1: values[29],
                info2: values[30],
                category: values[31],
                group: values[32],
                laborHours: parseFloat(values[33]) || 0,
                previousPrice: parseFloat(values[34]) || 0,
                priceChangeDate: values[35],
                costChangeDate: values[36],
                metalType: values[6].trim(),
                isSample: false
            };
            this.pricing.set(item.itemCode, item);
        }
    }
    
    /**
     * Load Bill of Materials from BM CSV export
     */
    loadBillOfMaterials(csvData) {
        // Parse BM-EXP01.csv
        const lines = csvData.trim().split('\n');
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split('|');
            const itemCode = values[0].trim();
            const component = {
                partNumber: values[1].trim(),
                class: values[2].trim(),
                quantity: parseFloat(values[3]) || 0,
                metalType: '' // Will need to get from IC data
            };
            
            // Get component metal type from inventory
            const part = this.inventory.get(component.partNumber);
            if (part) {
                component.metalType = part.metalHi + (part.metalLo || '');
            }
            
            if (!this.billOfMaterials.has(itemCode)) {
                this.billOfMaterials.set(itemCode, []);
            }
            this.billOfMaterials.get(itemCode).push(component);
        }
    }
}

// Export for use in web interface
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PricingCalculator;
}
