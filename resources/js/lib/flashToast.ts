import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;

        if (flash?.toast) {
            if (flash.toast.type === 'error') {
                toast.error(flash.toast.message);
            } else if (flash.toast.type === 'warning') {
                toast.warning(flash.toast.message);
            } else if (flash.toast.type === 'info') {
                toast.info(flash.toast.message);
            } else {
                toast.success(flash.toast.message);
            }
        }

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    });
}
