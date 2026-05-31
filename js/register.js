const BASE = "/PersonalTracker";
if (isLoggedIn()) {

    window.location.href = BASE +
        "/app/dashboard.html";

}

const registerForm =
    document.getElementById(
        "registerForm"
    );

if (registerForm) {

    registerForm.addEventListener(
        "submit",
        async (e) => {

            e.preventDefault();

            const name =
                document.getElementById(
                    "name"
                ).value;

            const email =
                document.getElementById(
                    "email"
                ).value;

            const password =
                document.getElementById(
                    "password"
                ).value;

            try {

                await apiRequest(
                    "/auth/register",
                    {
                        method: "POST",

                        body:
                            JSON.stringify({
                                name,
                                email,
                                password
                            })
                    }
                );

                window.location.href = BASE +
                    "/app/login.html";

            } catch (err) {

                alert(err.message);

            }

        }
    );

}