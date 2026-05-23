import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const validAppearances: Appearance[] = ['light', 'dark'];

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getCookie = (name: string): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    return (
        document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith(`${name}=`))
            ?.split('=')
            .slice(1)
            .join('=') ?? null
    );
};

const normalizeAppearance = (value: string | null | undefined): Appearance => {
    return validAppearances.includes(value as Appearance)
        ? (value as Appearance)
        : 'light';
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'light';
    }

    return normalizeAppearance(
        localStorage.getItem('appearance') ?? getCookie('appearance'),
    );
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    const nextAppearance = normalizeAppearance(value);

    document.documentElement.classList.toggle(
        'dark',
        nextAppearance === 'dark',
    );
    localStorage.setItem('appearance', nextAppearance);
    setCookie('appearance', nextAppearance);
}

export function initializeTheme(): void {
    const nextAppearance = getStoredAppearance();

    appearance.value = nextAppearance;
    updateTheme(nextAppearance);
}

const appearance = ref<Appearance>('light');

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        appearance.value = getStoredAppearance();
        updateTheme(appearance.value);
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        appearance.value === 'dark' ? 'dark' : 'light',
    );

    function updateAppearance(value: Appearance) {
        appearance.value = normalizeAppearance(value);
        updateTheme(appearance.value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
