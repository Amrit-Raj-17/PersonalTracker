const API_BASE_URL = "https://upcountry-fastball-unrushed.ngrok-free.dev/api";

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
                    "Content-Type": "application/json",
                    ...(token ? { Authorization: `Bearer ${token}` } : {})
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