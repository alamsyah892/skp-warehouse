<?php

namespace App\Providers\Filament;

use Andreia\FilamentNordTheme\FilamentNordThemePlugin;
use Andreia\FilamentUiSwitcher\FilamentUiSwitcherPlugin;
use Caresome\FilamentNeobrutalism\NeobrutalismeTheme;
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
            ->colors([
                'primary' => Color::Amber,
                // 'danger' => Color::Rose,
                // 'gray' => Color::Gray,
                // 'info' => Color::Blue,
                // 'primary' => Color::Indigo,
                // 'success' => Color::Emerald,
                // 'warning' => Color::Orange,
            ])
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
                FilamentInfoWidget::class,
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

            // ->spa()
            // ->spaUrlExceptions(fn(): array => [
            //     url('/admin'),
            //     PostResource::getUrl(),
            // ])

            // ->sidebarCollapsibleOnDesktop()
            // ->topNavigation()

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

                FilamentUiSwitcherPlugin::make()
                    ->iconRenderHook(PanelsRenderHook::USER_MENU_BEFORE)
                    ->withModeSwitcher()
                ,
                // NeobrutalismeTheme::make()
                //     ->customize([
                //         'border-width' => '2px',
                //         'shadow-offset-md' => '3px',
                //         'radius-md' => '0.5rem',
                //     ])
                // ,
                // FilamentNordThemePlugin::make(),

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
            ])
            // ->authGuard('admin')
        ;
    }
}
