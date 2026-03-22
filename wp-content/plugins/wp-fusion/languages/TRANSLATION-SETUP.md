# WP Fusion Translation Setup with Potomatic

This document explains how to use the automated translation system powered by [Potomatic](https://github.com/GravityKit/Potomatic) for WP Fusion.

## Quick Start

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Set up your OpenAI API key:**
   ```bash
   cp env-example.txt .env
   # Edit .env and add your OpenAI API key
   ```

3. **Generate translations:**
   ```bash
   npm run release
   ```

## Available Commands

### Development Commands
- `npm run translate:pot` - Generate .pot file only
- `npm run translate` - Generate translations from existing .pot file
- `npm run translate:mo` - Convert .po files to .mo files
- `npm run version:update` - Update version numbers in files

### Build Commands
- `npm run build` - Standard build (includes .pot generation)
- `npm run release` - Full release with translations

### Gulp Commands
- `gulp build` - Build without translations
- `gulp release` - Build with translations
- `gulp generatePotFile` - Generate .pot file
- `gulp generateTranslations` - Generate translations only
- `gulp createMoFiles` - Generate .mo files from .po files

## Configuration

### Environment Variables

Copy `env-example.txt` to `.env` and configure:

```bash
# Required
API_KEY=your_openai_api_key_here

# Optional (defaults shown)
MODEL=gpt-4o
TEMPERATURE=0.3
MAX_COST=50
BATCH_SIZE=15
MAX_RETRIES=3
VERBOSE_LEVEL=1
```

### Target Languages

Currently configured to translate to:
- Spanish (es_ES)
- French (fr_FR)
- German (de_DE)
- Italian (it_IT)
- Portuguese Brazil (pt_BR)
- Dutch (nl_NL)
- Russian (ru_RU)
- Japanese (ja)
- Chinese Simplified (zh_CN)
- Korean (ko_KR)

To modify languages, edit the `targetLanguages` array in `gulpfile.js`.

### Translation Dictionaries

The system uses specialized dictionaries for consistent translations:

- `config/dictionaries/common.json` - Core WP Fusion terminology
- `config/dictionaries/crm-terms.json` - CRM-specific terms
- `config/dictionaries/wordpress-terms.json` - WordPress-specific terms

### Custom Prompt

The translation prompt is in `config/prompt.md` and includes:
- Context about WP Fusion and its purpose
- Guidelines for WordPress terminology
- Instructions for handling technical terms
- Style and tone guidelines

### PO File Headers

Custom headers are defined in `config/po-header.json` and include:
- Project information
- Contact details
- Generator information
- Poedit configuration

## How It Works

1. **POT Generation**: WordPress CLI generates the .pot file with all translatable strings
2. **Translation**: Potomatic uses OpenAI to translate strings using context-aware prompts
3. **Dictionary Lookup**: Consistent terminology is ensured using specialized dictionaries
4. **PO File Creation**: Translated .po files are generated with proper headers
5. **MO File Generation**: Binary .mo files are created for WordPress to use

## File Structure

```
wp-fusion/
├── config/
│   ├── dictionaries/
│   │   ├── common.json
│   │   ├── crm-terms.json
│   │   └── wordpress-terms.json
│   ├── po-header.json
│   └── prompt.md
├── languages/
│   ├── wp-fusion.pot
│   ├── wp-fusion-es_ES.po
│   ├── wp-fusion-es_ES.mo
│   └── ... (other language files)
├── gulpfile.js
├── package.json
└── .env (you create this)
```

## Cost Management

Translation costs are controlled through:
- `MAX_COST` environment variable (default: $50)
- `BATCH_SIZE` for API efficiency (default: 15 strings per request)
- `MAX_RETRIES` for failed requests (default: 3)

## Troubleshooting

### Common Issues

1. **Missing .env file**: Copy `env-example.txt` to `.env` and configure
2. **No OpenAI API key**: Get one from https://platform.openai.com/api-keys
3. **Translation failures**: Check your API key, quota, and network connection
4. **Missing .mo files**: Run `npm run translate:mo` or check if `msgfmt` is installed

### Debug Mode

Set `VERBOSE_LEVEL=3` in your `.env` file for detailed debugging output.

### Manual Translation

If automatic translation fails, you can:
1. Generate the .pot file: `npm run translate:pot`
2. Use traditional translation tools like Poedit
3. Place .po files in the `languages/` directory
4. Generate .mo files: `npm run translate:mo`

## Integration with Release Process

The translation system is integrated into the standard build process:

- **Development**: Use `npm run build` (no translations)
- **Release**: Use `npm run release` (includes translations)

This ensures translations are always up-to-date when releasing new versions.

## Contributing

When adding new translatable strings:
1. Use proper WordPress i18n functions (`__()`, `_e()`, etc.)
2. Include translator comments for context:
   ```php
   // translators: %s is the CRM name
   sprintf( __( 'Connected to %s', 'wp-fusion' ), $crm_name )
   ```
3. Add new terminology to the appropriate dictionary files
4. Test translations with `npm run translate`

## Support

For translation-related issues:
1. Check the troubleshooting section above
2. Review Potomatic documentation: https://github.com/GravityKit/Potomatic
3. Open an issue with WP Fusion support 