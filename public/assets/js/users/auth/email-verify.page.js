import { resendVerificationEmail } from "./auth.api.js";
import {
    getRecaptchaToken,
    getErrorMessage,
    setButtonLoading,
} from "../../utils.js";
import { notification } from "../../ui/notification.js";

function initEmailVerifyPage(resendBtn) {
    resendBtn.addEventListener("click", resendEmail);

    async function resendEmail() {
        try {
            setButtonLoading(resendBtn, true);

            const recaptchaToken = await getRecaptchaToken(
                "resend_verification_email",
            );

            await resendVerificationEmail(recaptchaToken);

            notification.open(
                "Вам на электронную почту будет отправлен email для подтверждения",
            );
        } catch (e) {
            console.error(
                "[email-verify.page] Не удалось отправить email для подтверждения",
                {
                    message: e.message,
                    code: e.code,
                    status: e.status,
                    payload: e.payload,
                },
            );

            if (e.code === "EMAIL_RATE_LIMIT") {
                const sec = parseInt(e.retryAfter, 10);
                let text = "некоторое время";

                if (!Number.isNaN(sec) && sec > 0) {
                    text =
                        sec >= 60
                            ? `${Math.ceil(sec / 60)} мин.`
                            : `${sec} сек.`;
                }

                notification.open(
                    `Повторная отправка письма будет доступна через ${text}`,
                );
                return;
            }

            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            setButtonLoading(resendBtn, false);
        }
    }
}

const resendBtn = document.getElementById("resend-button");
if (resendBtn) initEmailVerifyPage(resendBtn);
