/**
 * Browser Keychain Detection Utilities
 * 
 * Provides feature detection for Web Authentication API, Credential Management API,
 * and other browser-based credential storage capabilities.
 */

export interface AuthenticatorCapabilities {
    webAuthn: boolean;
    credentialManagement: boolean;
    webCrypto: boolean;
    biometricAuth: boolean;
    platformAuthenticator: boolean;
    roamingAuthenticator: boolean;
    userVerification: boolean;
    residentKey: boolean;
}

export interface BrowserCapabilities {
    name: string;
    version: string;
    isSupported: boolean;
    capabilities: AuthenticatorCapabilities;
    limitations: string[];
    recommendations: string[];
}

/**
 * Check if Web Authentication API is supported
 */
export function isWebAuthnSupported(): boolean {
    return typeof window !== 'undefined' &&
           'navigator' in window &&
           'credentials' in navigator &&
           'create' in navigator.credentials &&
           'get' in navigator.credentials &&
           typeof PublicKeyCredential !== 'undefined';
}

/**
 * Check if Credential Management API is supported
 */
export function isCredentialManagementSupported(): boolean {
    return typeof window !== 'undefined' &&
           'navigator' in window &&
           'credentials' in navigator &&
           'store' in navigator.credentials &&
           typeof PasswordCredential !== 'undefined';
}

/**
 * Check if Web Crypto API is supported with required features
 */
export function isWebCryptoSupported(): boolean {
    return typeof window !== 'undefined' &&
           'crypto' in window &&
           'subtle' in window.crypto &&
           typeof window.crypto.subtle.generateKey === 'function' &&
           typeof window.crypto.subtle.encrypt === 'function' &&
           typeof window.crypto.subtle.decrypt === 'function';
}

/**
 * Check if biometric authentication is available
 */
export async function isBiometricAuthenticationAvailable(): Promise<boolean> {
    if (!isWebAuthnSupported()) {
        return false;
    }

    try {
        // Check if platform authenticator (TouchID/FaceID/Windows Hello) is available
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        return available;
    } catch (error) {
        console.warn('Failed to check biometric authentication availability:', error);
        return false;
    }
}

/**
 * Get detailed authenticator capabilities
 */
export async function getAuthenticatorCapabilities(): Promise<AuthenticatorCapabilities> {
    const capabilities: AuthenticatorCapabilities = {
        webAuthn: isWebAuthnSupported(),
        credentialManagement: isCredentialManagementSupported(),
        webCrypto: isWebCryptoSupported(),
        biometricAuth: false,
        platformAuthenticator: false,
        roamingAuthenticator: false,
        userVerification: false,
        residentKey: false,
    };

    if (capabilities.webAuthn) {
        try {
            capabilities.biometricAuth = await isBiometricAuthenticationAvailable();
            capabilities.platformAuthenticator = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            
            // Check for conditional mediation support (indicates modern WebAuthn support)
            if ('isConditionalMediationAvailable' in PublicKeyCredential) {
                capabilities.userVerification = await PublicKeyCredential.isConditionalMediationAvailable();
            }

            // Resident key support is typically available if platform authenticator is available
            capabilities.residentKey = capabilities.platformAuthenticator;
            
            // Roaming authenticators (USB security keys) are typically supported if WebAuthn is supported
            capabilities.roamingAuthenticator = true;
        } catch (error) {
            console.warn('Error checking authenticator capabilities:', error);
        }
    }

    return capabilities;
}

/**
 * Get browser-specific capabilities and limitations
 */
export async function getBrowserCapabilities(): Promise<BrowserCapabilities> {
    const userAgent = navigator.userAgent;
    let browserInfo = {
        name: 'Unknown',
        version: 'Unknown',
    };

    // Basic browser detection
    if (userAgent.includes('Chrome')) {
        browserInfo.name = 'Chrome';
        const match = userAgent.match(/Chrome\/(\d+)/);
        browserInfo.version = match ? match[1] : 'Unknown';
    } else if (userAgent.includes('Firefox')) {
        browserInfo.name = 'Firefox';
        const match = userAgent.match(/Firefox\/(\d+)/);
        browserInfo.version = match ? match[1] : 'Unknown';
    } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
        browserInfo.name = 'Safari';
        const match = userAgent.match(/Version\/(\d+)/);
        browserInfo.version = match ? match[1] : 'Unknown';
    } else if (userAgent.includes('Edge')) {
        browserInfo.name = 'Edge';
        const match = userAgent.match(/Edg\/(\d+)/);
        browserInfo.version = match ? match[1] : 'Unknown';
    }

    const capabilities = await getAuthenticatorCapabilities();
    const isSupported = capabilities.webAuthn || capabilities.credentialManagement;

    const limitations: string[] = [];
    const recommendations: string[] = [];

    // Browser-specific limitations and recommendations
    switch (browserInfo.name) {
        case 'Chrome':
            if (parseInt(browserInfo.version) < 67) {
                limitations.push('WebAuthn support requires Chrome 67+');
            }
            if (!capabilities.biometricAuth) {
                recommendations.push('Enable biometric authentication in Chrome settings');
            }
            break;

        case 'Firefox':
            if (parseInt(browserInfo.version) < 60) {
                limitations.push('WebAuthn support requires Firefox 60+');
            }
            limitations.push('Credential Management API not supported');
            recommendations.push('Consider using Chrome or Edge for full keychain support');
            break;

        case 'Safari':
            if (parseInt(browserInfo.version) < 14) {
                limitations.push('WebAuthn support requires Safari 14+');
            }
            limitations.push('Credential Management API not supported');
            limitations.push('Limited to TouchID/FaceID on supported devices');
            break;

        case 'Edge':
            if (parseInt(browserInfo.version) < 79) {
                limitations.push('Modern WebAuthn support requires Edge 79+');
            }
            break;

        default:
            limitations.push('Browser compatibility unknown');
            recommendations.push('Use Chrome, Firefox, Safari, or Edge for best support');
    }

    if (!capabilities.biometricAuth) {
        recommendations.push('Biometric authentication not available - ensure device supports TouchID/FaceID/Windows Hello');
    }

    return {
        name: browserInfo.name,
        version: browserInfo.version,
        isSupported,
        capabilities,
        limitations,
        recommendations,
    };
}

/**
 * Get recommended storage method based on browser capabilities
 */
export async function getRecommendedStorageMethod(): Promise<{
    method: 'keychain' | 'database' | 'hybrid';
    reason: string;
    confidence: 'high' | 'medium' | 'low';
}> {
    const capabilities = await getAuthenticatorCapabilities();

    if (capabilities.biometricAuth && capabilities.platformAuthenticator) {
        return {
            method: 'keychain',
            reason: 'Browser supports biometric authentication with platform authenticator',
            confidence: 'high',
        };
    }

    if (capabilities.webAuthn && capabilities.userVerification) {
        return {
            method: 'hybrid',
            reason: 'Browser supports WebAuthn but biometric auth may not be available',
            confidence: 'medium',
        };
    }

    if (capabilities.credentialManagement) {
        return {
            method: 'hybrid',
            reason: 'Browser supports Credential Management API but not WebAuthn',
            confidence: 'medium',
        };
    }

    return {
        method: 'database',
        reason: 'Browser does not support modern credential storage APIs',
        confidence: 'high',
    };
}

/**
 * Check if the current environment supports secure contexts
 */
export function isSecureContext(): boolean {
    return typeof window !== 'undefined' && window.isSecureContext;
}

/**
 * Get comprehensive browser keychain support report
 */
export async function getKeychainSupportReport(): Promise<{
    browser: BrowserCapabilities;
    secureContext: boolean;
    recommendation: Awaited<ReturnType<typeof getRecommendedStorageMethod>>;
    summary: string;
}> {
    const browser = await getBrowserCapabilities();
    const secureContext = isSecureContext();
    const recommendation = await getRecommendedStorageMethod();

    let summary = '';
    if (!secureContext) {
        summary = 'HTTPS required for browser keychain features';
    } else if (browser.isSupported && recommendation.method === 'keychain') {
        summary = 'Full browser keychain support available';
    } else if (browser.isSupported) {
        summary = 'Partial browser keychain support available';
    } else {
        summary = 'Browser keychain not supported - fallback to database storage';
    }

    return {
        browser,
        secureContext,
        recommendation,
        summary,
    };
}