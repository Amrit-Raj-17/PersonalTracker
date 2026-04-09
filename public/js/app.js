// Show current range value beside progress slider
document.addEventListener("DOMContentLoaded", () => {
    const progressInputs = document.querySelectorAll("input[type='range']");

    progressInputs.forEach(input => {
        const valueLabel = document.createElement("span");
        valueLabel.className = "range-value";
        valueLabel.innerText = input.value + "%";

        input.parentNode.insertBefore(valueLabel, input.nextSibling);

        input.addEventListener("input", () => {
            valueLabel.innerText = input.value + "%";
        });
    });

    // Confirm before deleting a task
    const deleteButtons = document.querySelectorAll(".btn-delete");

    deleteButtons.forEach(button => {
        button.addEventListener("click", (e) => {
            const confirmDelete = confirm("Are you sure you want to delete this task?");

            if (!confirmDelete) {
                e.preventDefault();
            }
        });
    });

    // Mark progress bar widths automatically
    const progressBars = document.querySelectorAll(".progress-fill");

    progressBars.forEach(bar => {
        const value = bar.getAttribute("data-progress");

        if (value) {
            bar.style.width = value + "%";
        }
    });

    // Toggle completed tasks section
    const toggleButton = document.getElementById("toggleCompleted");
    const completedSection = document.getElementById("completedTasks");

    if (toggleButton && completedSection) {
        toggleButton.addEventListener("click", () => {
            if (completedSection.style.display === "none") {
                completedSection.style.display = "block";
                toggleButton.innerText = "Hide Completed Tasks";
            } else {
                completedSection.style.display = "none";
                toggleButton.innerText = "Show Completed Tasks";
            }
        });
    }
});