<?php

namespace App\Providers\Filament;

// use Andreia\FilamentUiSwitcher\FilamentUiSwitcherPlugin;
use App\Filament\Resources\Banks\BankResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\Currencies\CurrencyResource;
use App\Filament\Resources\Divisions\DivisionResource;
use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use Awcodes\Gravatar\GravatarPlugin;
use Awcodes\Gravatar\GravatarProvider;
use Awcodes\Overlook\OverlookPlugin;
use Awcodes\Overlook\Widgets\OverlookWidget;
use Awcodes\StickyHeader\StickyHeaderPlugin;
use Awcodes\Versions\VersionsPlugin;
use Awcodes\Versions\VersionsWidget;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile(isSimple: false)
            // ->colors([
            //     'primary' => Color::Indigo,

            //     'danger' => Color::Rose,
            //     'gray' => Color::Gray,
            //     'info' => Color::Blue,
            //     'primary' => Color::Indigo,
            //     'success' => Color::Emerald,
            //     'warning' => Color::Orange,
            // ])
            ->maxContentWidth(Width::Full)
            ->sidebarWidth('16rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                    // VersionsWidget::class,
                    // FilamentInfoWidget::class,
                OverlookWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('User Management')
                    ->collapsed()
                ,
                NavigationGroup::make()
                    ->label('General') // untuk resource tanpa group
                // ->collapsible(false)
                ,
                NavigationGroup::make()
                    ->label('Purchasing')
                ,
                NavigationGroup::make()
                    ->label('Settings')
                ,
            ])
            // ->sidebarFullyCollapsibleOnDesktop()
            ->topNavigation()

            // ->spa()
            // ->spaUrlExceptions(fn(): array => [
            //     url('/admin'),
            //     PostResource::getUrl(),
            // ])

            // ->sidebarCollapsibleOnDesktop()
            // ->topNavigation()

            ->defaultAvatarProvider(GravatarProvider::class)
            ->plugins([
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        userMenuLabel: 'My Profile', // Customizes the 'account' link label in the panel User Menu (default = null)
                        shouldRegisterNavigation: true, // Adds a main navigation item for the My Profile page (default = false)
                        navigationGroup: 'Settings', // Sets the navigation group for the My Profile page (default = null)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->enableTwoFactorAuthentication(
                        force: false, // force the user to enable 2FA before they can use the application (default = false)
                        // action: CustomTwoFactorPage::class, // optionally, use a custom 2FA page
                        // authMiddleware: MustTwoFactor::class, // optionally, customize 2FA auth middleware or disable it to register manually by setting false
                        scopeToPanel: true, // scope the 2FA only to the current panel (default = true)
                    )
                    ->avatarUploadComponent(fn($fileUpload) => $fileUpload->disableLabel())
                    // ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('avatars'))
                    ->enableBrowserSessions(condition: true)
                ,

                EnvironmentIndicatorPlugin::make()
                    ->visible(fn() => auth()->user()?->hasRole('Project Owner'))
                    ->showBadge(true)
                    ->badgePosition(PanelsRenderHook::TOPBAR_LOGO_BEFORE)
                    ->showBorder(false)
                    ->color(fn() => match (app()->environment()) {
                        'production' => Color::Rose,
                        'staging' => Color::Orange,
                        default => Color::Indigo,
                    })
                    ->showDebugModeWarningInProduction()
                ,

                OverlookPlugin::make()
                    ->sort(2)
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                        'xl' => 4,
                        '2xl' => null,
                    ])
                    ->includes([
                        CompanyResource::class,
                        WarehouseResource::class,
                        DivisionResource::class,
                        ProjectResource::class,
                        ItemCategoryResource::class,
                        ItemResource::class,
                        VendorResource::class,
                        CourierResource::class,
                        CurrencyResource::class,
                        BankResource::class,
                    ])
                    ->withoutTrashed()
                ,

                StickyHeaderPlugin::make()
                    ->floating()
                    ->colored()
                ,

                GlobalSearchModalPlugin::make(),

                VersionsPlugin::make(),

                GravatarPlugin::make()
                    ->default('robohash')
                    ->size(200)
                    ->rating('pg')
                ,

                // FilamentUiSwitcherPlugin::make()
                //     ->iconRenderHook(PanelsRenderHook::USER_MENU_BEFORE)
                //     ->withModeSwitcher()
                // ,
            ])
            // ->authGuard('admin')
        ;
    }
}
