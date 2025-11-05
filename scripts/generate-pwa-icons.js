#!/usr/bin/env node

/**
 * Generate PWA Icons
 *
 * This script generates all required PWA icon sizes from the SVG template
 * Uses sharp for high-quality SVG to PNG conversion
 */

import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const inputSvg = path.join(__dirname, '../public/images/pwa-icon-template.svg');
const outputDir = path.join(__dirname, '../public/images');

// Icon sizes to generate
const iconSizes = [
    { size: 192, name: 'pwa-192x192.png', purpose: 'any' },
    { size: 512, name: 'pwa-512x512.png', purpose: 'any' },
    { size: 192, name: 'pwa-192x192-maskable.png', purpose: 'maskable', padding: 0.1 },
    { size: 512, name: 'pwa-512x512-maskable.png', purpose: 'maskable', padding: 0.1 },
    { size: 180, name: 'apple-touch-icon.png', purpose: 'apple' }, // iOS
    { size: 16, name: 'favicon-16x16.png', purpose: 'favicon' },
    { size: 32, name: 'favicon-32x32.png', purpose: 'favicon' },
];

async function generateIcons() {
    console.log('🎨 Generating PWA icons from SVG template...\n');

    // Ensure output directory exists
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    // Check if input SVG exists
    if (!fs.existsSync(inputSvg)) {
        console.error(`❌ Error: SVG template not found at ${inputSvg}`);
        process.exit(1);
    }

    let successCount = 0;
    let errorCount = 0;

    // Generate each icon size
    for (const icon of iconSizes) {
        try {
            const outputPath = path.join(outputDir, icon.name);

            // Calculate padding for maskable icons
            let processedSize = icon.size;
            let padding = 0;

            if (icon.purpose === 'maskable' && icon.padding) {
                padding = Math.floor(icon.size * icon.padding);
                processedSize = icon.size - (padding * 2);
            }

            // Generate PNG from SVG
            if (icon.purpose === 'maskable') {
                // For maskable icons, add padding/safe area
                await sharp(inputSvg)
                    .resize(processedSize, processedSize)
                    .extend({
                        top: padding,
                        bottom: padding,
                        left: padding,
                        right: padding,
                        background: { r: 8, g: 145, b: 178, alpha: 1 } // Cyan-600 background
                    })
                    .png({ quality: 100 })
                    .toFile(outputPath);
            } else {
                // Standard icons
                await sharp(inputSvg)
                    .resize(icon.size, icon.size)
                    .png({ quality: 100 })
                    .toFile(outputPath);
            }

            console.log(`✅ Generated ${icon.name} (${icon.size}x${icon.size}${icon.purpose === 'maskable' ? ' maskable' : ''})`);
            successCount++;

        } catch (error) {
            console.error(`❌ Error generating ${icon.name}:`, error.message);
            errorCount++;
        }
    }

    // Generate favicon.ico (optional, requires multiple sizes)
    try {
        const favicon16 = path.join(outputDir, 'favicon-16x16.png');
        const favicon32 = path.join(outputDir, 'favicon-32x32.png');
        const faviconIco = path.join(__dirname, '../public/favicon.ico');

        // Note: Sharp doesn't support ICO format directly
        // For now, we'll just copy the 32x32 version as a fallback
        // In production, use a dedicated ICO converter
        console.log('\n⚠️  Note: For proper favicon.ico support, use an ICO converter tool');
        console.log('   You can use https://www.icoconverter.com/ or similar');

    } catch (error) {
        console.error('⚠️  Could not generate favicon.ico:', error.message);
    }

    console.log(`\n✨ Icon generation complete!`);
    console.log(`   Success: ${successCount}`);
    console.log(`   Errors: ${errorCount}`);

    if (errorCount > 0) {
        process.exit(1);
    }
}

// Run the script
generateIcons().catch(error => {
    console.error('❌ Fatal error:', error);
    process.exit(1);
});
