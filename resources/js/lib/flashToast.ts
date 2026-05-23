import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;

        if (flash?.toast) {
            toast[flash.toast.type](flash.toast.message);
        }

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    });
}
