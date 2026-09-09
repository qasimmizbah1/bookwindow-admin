<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GlobalSettingResource\Pages;
use App\Models\GlobalSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GlobalSettingResource extends Resource
{
    protected static ?string $model = GlobalSetting::class;

    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Global Settings';
    protected static ?string $modelLabel = 'Global Setting';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'global-settings';

    /**
     * Strictly restricted to Admins only; Vendors cannot see or access this resource.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Update Information')
                    ->schema([
                        Forms\Components\Placeholder::make('last_updated_at')
                            ->label('Last Updated')
                            ->content(function (?GlobalSetting $record) {
                                return $record?->updated_at
                                    ? $record->updated_at->format('d M Y, h:i A')
                                    : 'Not updated yet';
                            }),

                        Forms\Components\Placeholder::make('updated_by_name')
                            ->label('Updated By')
                            ->content(function (?GlobalSetting $record) {
                                return $record?->updatedBy?->name ?? 'System';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Tabs::make('Global Settings')
                    ->tabs([
                        // TAB 1: BRANDING & LOGO
                        Forms\Components\Tabs\Tab::make('Branding & Assets')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('Website Identity')
                                    ->description('Configure website name, tagline, and copyright information.')
                                    ->schema([
                                        Forms\Components\TextInput::make('site_name')
                                            ->label('Website Title / Name')
                                            ->placeholder('Bookwindow')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('site_tagline')
                                            ->label('Tagline / Slogan')
                                            ->placeholder("India's Trusted Online Bookstore")
                                            ->maxLength(150),

                                        Forms\Components\TextInput::make('footer_copyright')
                                            ->label('Footer Copyright Notice')
                                            ->placeholder('© 2026 Bookwindow. All rights reserved.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Logos & Favicon')
                                    ->description('Upload your store header logo, dark/footer logo, and browser favicon.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('site_logo')
                                            ->label('Primary Logo (Header)')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->helperText('Recommended: Transparent PNG or SVG (approx. 250×60 px).'),

                                        Forms\Components\FileUpload::make('site_logo_dark')
                                            ->label('Dark / Alternative Logo (Footer)')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->helperText('Used on dark footers or dark backgrounds.'),

                                        Forms\Components\FileUpload::make('site_favicon')
                                            ->label('Browser Favicon')
                                            ->acceptedFileTypes([
                                                'image/x-icon',
                                                'image/vnd.microsoft.icon',
                                                'image/png',
                                                'image/svg+xml',
                                            ])
                                            ->disk('public')
                                            ->directory('settings')
                                            ->visibility('public')
                                            ->helperText('Square icon displayed in browser tabs (.ico, .png, or .svg).'),
                                    ])
                                    ->columns(3),
                            ]),

                        // TAB 2: SCRIPTS & TRACKING
                        Forms\Components\Tabs\Tab::make('Scripts & Analytics')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Forms\Components\Section::make('Google Tag Manager (GTM)')
                                    ->description('Inject Google Tag Manager code snippets directly into your storefront.')
                                    ->schema([
                                        Forms\Components\Textarea::make('gtm_head_code')
                                            ->label('GTM Head Script (<head>)')
                                            ->placeholder("<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){...})(window,document,'script','dataLayer','GTM-XXXXXX');</script>\n<!-- End Google Tag Manager -->")
                                            ->rows(6)
                                            ->helperText('Paste GTM script snippet to be placed in the <head> tag.')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('gtm_body_code')
                                            ->label('GTM Body NoScript (<body>)')
                                            ->placeholder("<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=GTM-XXXXXX\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->")
                                            ->rows(4)
                                            ->helperText('Paste GTM (noscript) code to be placed immediately after <body>.')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Google Analytics & Search Console')
                                    ->description('Configure GA4 Measurement ID and tracking code.')
                                    ->schema([
                                        Forms\Components\TextInput::make('ga_measurement_id')
                                            ->label('GA4 Measurement ID')
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->helperText('e.g. G-Q8BCCV1SLL'),

                                        Forms\Components\Textarea::make('ga_script_code')
                                            ->label('Additional Google Analytics / gtag Snippet')
                                            ->rows(4)
                                            ->placeholder("<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX\"></script>\n<script>\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n  gtag('config', 'G-XXXXXXXXXX');\n</script>")
                                            ->helperText('Optional standalone gtag.js code if not loaded via GTM.')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Meta / Facebook Pixel')
                                    ->description('Configure Facebook Meta Pixel for ads tracking and conversion measurement.')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_pixel_id')
                                            ->label('Meta Pixel ID')
                                            ->placeholder('123456789012345')
                                            ->helperText('Your 15-16 digit Meta Pixel numeric ID.'),

                                        Forms\Components\Textarea::make('meta_pixel_code')
                                            ->label('Meta Pixel Base Script & NoScript')
                                            ->rows(6)
                                            ->placeholder("<!-- Meta Pixel Code -->\n<script>\n!function(f,b,e,v,n,t,s)...\nfbq('init', '123456789012345');\nfbq('track', 'PageView');\n</script>\n<!-- End Meta Pixel Code -->")
                                            ->helperText('Paste full Meta Pixel base snippet here.')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Custom Head & Footer Scripts')
                                    ->description('Raw HTML/JS/CSS code injection into storefront head and body.')
                                    ->schema([
                                        Forms\Components\Textarea::make('custom_head_scripts')
                                            ->label('Custom <head> Scripts & Tags')
                                            ->rows(5)
                                            ->placeholder("<meta name=\"google-site-verification\" content=\"...\">\n<link rel=\"preconnect\" href=\"...\">")
                                            ->helperText('Inserted inside <head>. Ideal for site verification tags, fonts, Microsoft Clarity, or custom CSS.')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('custom_footer_scripts')
                                            ->label('Custom </body> Footer Scripts')
                                            ->rows(5)
                                            ->placeholder("<!-- Live Chat Widget / Custom JS -->\n<script src=\"...\"></script>")
                                            ->helperText('Inserted immediately before </body>. Ideal for Live Chat (Tawk.to, WhatsApp floating chat, Hotjar, custom JS).')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB 3: CONTACT & SUPPORT
                        Forms\Components\Tabs\Tab::make('Contact & Support')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make('Store Support & Contact Channels')
                                    ->description('Official contact details displayed across store header, footer, and emails.')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_email')
                                            ->label('Support Email Address')
                                            ->email()
                                            ->placeholder('support@bookwindow.in'),

                                        Forms\Components\TextInput::make('contact_phone')
                                            ->label('Support Phone / Helpline')
                                            ->tel()
                                            ->placeholder('+91 9876543210'),

                                        Forms\Components\TextInput::make('contact_whatsapp')
                                            ->label('WhatsApp Support Number')
                                            ->tel()
                                            ->placeholder('+91 9876543210')
                                            ->helperText('Customer direct WhatsApp chat connection.'),

                                        Forms\Components\TextInput::make('business_hours')
                                            ->label('Support Working Hours')
                                            ->placeholder('Mon - Sat: 10:00 AM - 7:00 PM'),

                                        Forms\Components\Textarea::make('contact_address')
                                            ->label('Physical Office / Store Address')
                                            ->rows(3)
                                            ->placeholder("Bookwindow, Main Market, Jaipur, Rajasthan, India - 302001")
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        // TAB 4: SOCIAL MEDIA LINKS
                        Forms\Components\Tabs\Tab::make('Social Links')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Forms\Components\Section::make('Official Social Media Profiles')
                                    ->description('Direct links to your brand social networks displayed on the website.')
                                    ->schema([
                                        Forms\Components\TextInput::make('social_facebook')
                                            ->label('Facebook Profile / Page URL')
                                            ->url()
                                            ->placeholder('https://facebook.com/bookwindow'),

                                        Forms\Components\TextInput::make('social_instagram')
                                            ->label('Instagram Profile URL')
                                            ->url()
                                            ->placeholder('https://instagram.com/bookwindow'),

                                        Forms\Components\TextInput::make('social_twitter')
                                            ->label('Twitter / X Profile URL')
                                            ->url()
                                            ->placeholder('https://x.com/bookwindow'),

                                        Forms\Components\TextInput::make('social_youtube')
                                            ->label('YouTube Channel URL')
                                            ->url()
                                            ->placeholder('https://youtube.com/@bookwindow'),

                                        Forms\Components\TextInput::make('social_linkedin')
                                            ->label('LinkedIn Profile URL')
                                            ->url()
                                            ->placeholder('https://linkedin.com/company/bookwindow'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_name')
                    ->label('Website Name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('site_logo')
                    ->label('Logo'),
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGlobalSettings::route('/'),
            'edit' => Pages\EditGlobalSetting::route('/{record}/edit'),
        ];
    }

    /**
     * Resolve record for edit route. Auto-creates record #1 if the table is currently empty.
     */
    public static function resolveRecordRouteBinding(int | string $key): ?\Illuminate\Database\Eloquent\Model
    {
        return GlobalSetting::firstOrCreate(
            ['id' => $key],
            [
                'site_name' => 'Bookwindow',
                'site_tagline' => "India's Trusted Online Bookstore",
                'footer_copyright' => '© ' . date('Y') . ' Bookwindow. All rights reserved.',
            ]
        );
    }

    /**
     * Direct Navigation: Clicking 'Global Settings' opens the Edit page directly.
     */
    public static function getNavigationUrl(): string
    {
        $record = GlobalSetting::current();

        return static::getUrl('edit', ['record' => $record->id]);
    }
}
