// This script is used to add new video fields to the form when the user clicks on the "Add Video" button.
const addVideoButton = document.getElementById("add-video");
const videoList = document.getElementById("videos-list");

document.addEventListener("DOMContentLoaded", function () {
    if (addVideoButton) {
        addVideoButton.addEventListener("click", () => {
            const index = videoList.querySelectorAll("input").length;
            const newWidget = videoList.dataset.prototype.replace(/__name__/g, index);

            const newVideoElement = document.createElement("div");
            newVideoElement.innerHTML = newWidget;

            const videoInputs = newVideoElement.querySelectorAll("input");
            videoInputs.forEach((input) => {
                input.style.marginBottom = "10px";
            });

            videoList.insertAdjacentElement("beforeend", newVideoElement);
        });
    }
});

// This script is used to trick pagination on the homepage and the tricks list page.
document.addEventListener("DOMContentLoaded", function () {
    let offset = 15;
    const loadMoreButton = document.getElementById("load-more-tricks");

    if (loadMoreButton) {
        loadMoreButton.addEventListener("click", function () {
            fetch(`/tricks/load-more/${offset}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const tricksList = document.getElementById("tricks-list");
                    tricksList.insertAdjacentHTML("beforeend", data.html);

                    offset += 5;

                    if (!data.hasMore) {
                        loadMoreButton.style.display = "none";
                    }
                })
                .catch(error => console.error("Error loading more tricks:", error));
        });
    }
});


