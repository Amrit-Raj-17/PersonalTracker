// daily.js

requireAuth();

let dailyGoals = [];

document.addEventListener("DOMContentLoaded", async () => {
    setupEvents();

    await logVisit();

    await loadGoals();
});

function setupEvents() {
    document
        .getElementById("addGoalBtn")
        ?.addEventListener("click", openModal);

    document
        .getElementById("closeGoalModal")
        ?.addEventListener("click", closeModal);

    document
        .getElementById("goalForm")
        ?.addEventListener("submit", saveGoal);
}

async function loadGoals() {
    try {

        dailyGoals = await apiRequest(
            "/daily"
        );

        renderGoals();

        updateSummary();

    } catch (err) {
        console.error(err);
    }
}

function renderGoals() {

    const container =
        document.getElementById(
            "dailyContainer"
        );

    if (!container) return;

    container.innerHTML = "";

    if (dailyGoals.length === 0) {

        container.innerHTML = `
            <p>
                No daily goals found.
            </p>
        `;

        return;
    }

    dailyGoals.forEach(goal => {

        container.innerHTML += `
            <div class="daily-card">

                <div class="daily-info">

                    <h3>
                        ${goal.templateId.title}
                    </h3>

                    <p>
                        ${goal.templateId.description}
                    </p>

                    <small>
                        🔥 ${goal.streak}
                        day streak
                    </small>

                </div>

                <div>

                    <input
                        type="checkbox"
                        ${
                            goal.completed
                                ? "checked"
                                : ""
                        }
                        id="${goal._id}"

                        onchange="
                            toggleGoal(this)
                        "
                    >

                </div>

            </div>
        `;
    });
}

function toggleGoal(node) {

    const id = node.id;

    const goal =
        dailyGoals.find(
            g => g._id === id
        );

    if (!goal) return;

    goal.completed =
        !goal.completed;

    updateSummary();

    apiRequest(
        `/daily/toggle`,
        {
            method: "POST",
            body: JSON.stringify({
                id
            })
        }
    );
}

function updateSummary() {

    const total =
        dailyGoals.length;

    const completed =
        dailyGoals.filter(
            g => g.completed
        ).length;

    const remaining =
        total - completed;

    const percentage =
        total === 0
            ? 0
            : Math.round(
                (
                    completed /
                    total
                ) * 100
            );

    document.getElementById(
        "completedCount"
    ).textContent = completed;

    document.getElementById(
        "remainingCount"
    ).textContent = remaining;

    document.getElementById(
        "completionPercent"
    ).textContent =
        `${percentage}%`;

    document.getElementById(
        "dailyProgress"
    ).style.width =
        `${percentage}%`;
}

function openModal() {

    document
        .getElementById(
            "goalForm"
        )
        ?.reset();

    document
        .getElementById(
            "goalModal"
        )
        ?.classList.remove(
            "hidden"
        );
}

function closeModal() {

    document
        .getElementById(
            "goalModal"
        )
        ?.classList.add(
            "hidden"
        );
}

async function saveGoal(e) {

    e.preventDefault();

    const title =
        document
        .getElementById(
            "goalTitle"
        )
        .value
        .trim();

    const description =
        document
        .getElementById(
            "goalDescription"
        )
        .value
        .trim();

    if (!title) return;

    const newGoal = {

        id: Date.now(),

        title,

        description,

        completed: false,

        streak: 0
    };

    dailyGoals.push(
        newGoal
    );

    renderGoals();

    updateSummary();

    closeModal();

    await apiRequest(
        "/daily",
        {
            method: "POST",
            body: JSON.stringify({
                title,
                description
            })
        }
    );
}

async function logVisit() {

    await apiRequest(
        "/visits/log",
        {
            method: "POST",

            body: JSON.stringify({
                action:
                    "OPEN_DAILY"
            })
        }
    );
}