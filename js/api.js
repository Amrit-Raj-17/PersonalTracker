const API_BASE_URL = "http://localhost:8080/api";

async function apiRequest(
    endpoint,
    options = {}
) {

    const token =
        localStorage.getItem("jwt");

    const response =
        await fetch(
            API_BASE_URL + endpoint,
            {
                ...options,

                headers: {

                    "Content-Type":
                        "application/json",

                    Authorization:
                        token
                            ? `Bearer ${token}`
                            : "",

                    ...(options.headers || {})
                }
            }
        );

    const data =
        await response.json();

    if (!response.ok) {

        throw new Error(
            data.message ||
            "Something went wrong"
        );

    }

    return data;
}