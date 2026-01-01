<!DOCTYPE html>
<html lang="en">
    <head>
        <title>About US</title>
        <?php include('helperFiles/headData.php'); ?>

        <style>
            :root {
                --primary-blue: #2563eb;
                --light-blue: #eff6ff;
                --white: #ffffff;
                --text-dark: #1e293b;
                --text-light: #475569;
            }

            body {
                margin: 0;
                background: linear-gradient(rgba(255,255,255,0.35),rgba(255,255,255,0.35)),url('img/about photos/bg.jpeg') no-repeat center center fixed; /* Set the background image */
                background-size: cover; /* Cover the entire viewport */
                color: var(--text-dark);
                font-family: "Raleway", sans-serif;
                min-height: 100vh;
                position: relative; /* Position relative for overlay */
            }

            /* Overlay for faded effect */

            .container {
                margin: 0 auto;
                padding: 2rem;
                position: relative; /* Position relative for z-index */
                z-index: 2; /* Ensure container is above the overlay */
            }

            h1, h2 {
                text-align: center;
                color: var(--primary-blue);
            }

            h1 {
                font-size: 3.5rem; /* Increased size */
                margin-bottom: 0.5rem;
            }

            h2 {
                font-size: 2.5rem; /* Increased size */
                margin-top: 3rem;
                margin-bottom: 1rem;
            }

            p {
                font-size: 1.2rem; /* Increased size */
                line-height: 1.8;
            }

            .gallery {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-top: 2rem;
                position: relative;
                height: 400px; /* Set a fixed height for the gallery */
                overflow: hidden; /* Hide overflow */
            }

            .gallery-item {
                position: absolute;
                width: 100%; /* Full width for large display */
                transition: transform 0.5s ease, opacity 0.5s ease;
                opacity: 0;
                transform: translateY(50px);
                border: 2px solid var(--primary-blue);
                border-radius: 10px;
                background: var(--white);
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                padding: 10px;
                text-align: center;
            }

            .gallery-item.active {
                opacity: 1;
                transform: translateY(0);
            }

            .gallery-item img {
                width: 100%;
                height: auto;
                border-radius: 8px;
            }

            .gallery-item h4 {
                margin-top: 10px;
                font-size: 1.2rem;
                color: var(--text-dark);
                font-style: italic;
            }

            /* Team Section Styles */
            .team-section {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
                margin-top: 2rem;
            }

            .member-card {
                background: var(--white);
                padding: 1.5rem;
                border-left: 6px solid var(--primary-blue);
                border-radius: .3rem;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                transition: transform 0.3s;
            }

            .member-card:hover {
                transform: translateY(-4px);
            }

            .member-card h3 {
                margin-top: 0;
                font-size: 1.6rem; /* Increased size */
                color: var(--primary-blue);
            }

            .member-card p {
                margin: 0.5rem 0 0;
                font-size: 1rem; /* Increased size */
                color: var(--text-light);
            }

            .member-card img {
                width: 150px; /* Set a fixed width for the images */
                height: 200px;
                object-fit: cover;
                border-radius: 10px; /* Optional: make images circular */
                margin-bottom: 1rem; /* Space between image and text */
            }

            .member-card {
                text-align: center; /* Center align text */
                padding: 1rem; /* Add padding */
                border: 1px solid var(--primary-blue); /* Optional: border for aesthetic */
                border-radius: 10px; /* Rounded corners */
                background: var(--white); /* Background color */
                box-shadow: 0 4px 10px rgba(0,0,0,0.1); /* Shadow for depth */
                transition: transform 0.3s; /* Smooth hover effect */
            }

            .member-card:hover {
                transform: translateY(-4px); /* Lift effect on hover */
            }

            /* Polaroid Style */
            .polaroid {
                background: white;
                padding: 10px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                transition: transform 0.3s ease;
                border-radius: 10px;
                position: relative;
                overflow: hidden;
            }

            .polaroid:hover {
                transform: scale(1.05) rotate(-1deg);
            }

            .polaroid img {
                border-radius: 8px;
                width: ;
            }
        
            .team-lead{
                width: 600px;
                display: flex;
            }
            .leader-section{
                display: flex;
                justify-content: center;
            }
        </style>
    </head>
    <body>
        <div class="overlay"></div> <!-- Overlay for faded effect -->

        <!-- Header and Navbar -->
        <?php include('helperFiles/header.php'); ?>

        <div class="container">
            <h1>About Us</h1>
            <h3 style="text-align: center; max-width: 700px; margin: 0 auto; color: var(--text-light);">
                We were a group of Grade 10 students from the Philippine Science High School - Ilocos Region Campus (PSHS-IRC), enrolled in the Full Stack Web Development (FSWD) Elective during the School Year 2024–2025. This project is a system that streamlines the process of borrowing laboratory equipment, making it more efficient and hassle-free for research personnel.
            </h3>

            <h2>Meet the Team</h2>
            <div class="leader-section">
                <!-- Head of the Team -->
                <div class="team-lead">
                    <div class="member-card lead">
                        <img src="img/about photos/gab.jpeg" alt="Gabriel James Valdez">
                        <h3>Gabriel James Valdez</h3>
                        <h4>Head of the Team & UI/UX Designer. Obsessed with human-centered design and flow.</h4>
                    </div>
                </div> 
            </div>
            
            <!-- Other Members -->
            <div class="team-section">
                <div class="member-card">
                    <img src="img/about photos/zyx.jpeg" alt="Zyx Leiabe A. Barangan">
                    <h3>Zyx Leiabe A. Barangan</h3>
                    <h4>Founder & Lead Developer. Passionate about clean code and creative problem solving.</h4>
                </div>
                <div class="member-card">
                    <img src="img/about photos/ben.jpeg" alt="Christian Benedict U. Soy">
                    <h3>Christian Benedict U. Soy</h3>
                    <h4>Content Strategist. Makes ideas come to life with words and clarity.</h4>
                </div>
                <div class="member-card">
                    <img src="img/about photos/rojan.jpeg" alt="Rojan Joefel C. Dumlao">
                    <h3>Rojan Joefel C. Dumlao</h3>
                    <h4>Backend Engineer. Keeps the engines running with logic and power.</h4>
                </div>
                <div class="member-card">
                    <img src="img/about photos/veca.jpg" alt="Riveca T. Pamerol">
                    <h3>Riveca T. Pamerol</h3>
                    <h4>Design Helper. Public Health Student who chose FSWD as the first choice for her G10 Elective.</h4>
                </div>
            </div>

            <h2>Gallery</h2>
            <div class="gallery">
                <div class="gallery-item" data-img-src="img/about photos/4.jpg">
                    <img src="img/about photos/4.jpg" alt="Exploring Ideas with Team">
                    <h4>Designing the Future</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/10.jpg">
                    <img src="img/about photos/10.jpg" alt="Team Brainstorming">
                    <h4>Creative Collaboration</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/6.jpeg">
                    <img src="img/about photos/6.jpeg" alt="Sketching Ideas">
                    <h4>Vision into Reality</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/7.jpeg">
                    <img src="img/about photos/7.jpeg" alt="Team Collaboration">
                    <h4>Building Connections</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/8.jpeg">
                    <img src="img/about photos/8.jpeg" alt="Celebrating Progress">
                    <h4>Success in Unity</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/9.jpeg">
                    <img src="img/about photos/9.jpeg" alt="Creative Brainstorming">
                    <h4>Moments of Innovation</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/11.jpg">
                    <img src="img/about photos/11.jpg" alt="Whiteboard Planning">
                    <h4>Strategizing Together</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/12.jpg">
                    <img src="img/about photos/12.jpg" alt="Hands-On Prototyping">
                    <h4>Turning Ideas to Action</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/13.jpg">
                    <img src="img/about photos/13.jpg" alt="Pitch Presentation">
                    <h4>Presenting with Passion</h4>
                </div>
                <div class="gallery-item" data-img-src="img/about photos/14.jpg">
                    <img src="img/about photos/14.jpg" alt="Peer Feedback Session">
                    <h4>Learning Through Feedback</h4>
                </div>
            </div>


            <!-- Journey We Took Section -->
            <h2>Coding Camp</h2>
            <div class="gallery">
                <div class="gallery-item active polaroid" data-img-src="img/journey/photo1.jpg">
                    <img src="img/about photos/1.jpg" alt="Coding Camp">
                    <h4>Coding Camp</h4>
                </div>
                <div class="gallery-item polaroid" data-img-src="img/journey/photo2.jpg">
                    <img src="img/about photos/2.jpg" alt="Coding Camp">
                    <h4>Coding Camp</h4>
                </div>
                <div class="gallery-item polaroid" data-img-src="img/journey/photo3.jpg">
                    <img src="img/about photos/3.jpg" alt="Coding Camp">
                    <h4>Coding Camp</h4>
                </div>
                <div class="gallery-item polaroid" data-img-src="img/journey/photo4.jpg">
                    <img src="img/about photos/5.jpg" alt="Coding Camp">
                    <h4>Coding Camp</h4>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="galleryModal" tabindex="-1" role="dialog" aria-labelledby="galleryModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="galleryModalLabel">Gallery Image</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <img src="" id="modalImage" alt="Gallery Image" style="width: 100%;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <?php include 'helperFiles/footer.php'; ?>

        <script>
            $(document).ready(function () {
                // Set the modal image source when a gallery item is clicked
                $('.gallery-item').on('click', function () {
                    var imgSrc = $(this).data('img-src');
                    $('#modalImage').attr('src', imgSrc);
                });

                // Function to handle synchronized galleries
                function handleSynchronizedGalleries(gallerySelector) {
                    let currentIndex = 0;

                    // Select all galleries (e.g., both .gallery sections)
                    const galleries = $(gallerySelector);

                    // Extract the gallery items per gallery and group them by index
                    const itemGroups = [];

                    // Assume all galleries have the same number of items
                    const totalItems = galleries.first().find('.gallery-item').length;

                    for (let i = 0; i < totalItems; i++) {
                        let group = [];
                        galleries.each(function () {
                            const item = $(this).find('.gallery-item').eq(i);
                            group.push(item);
                        });
                        itemGroups.push(group);
                    }

                    // Initially show the first group
                    itemGroups[0].forEach(item => item.addClass('active'));

                    function showNextGroup() {
                        itemGroups[currentIndex].forEach(item => item.removeClass('active'));
                        currentIndex = (currentIndex + 1) % totalItems;
                        itemGroups[currentIndex].forEach(item => item.addClass('active'));
                    }

                    // Set interval for automatic change
                    setInterval(showNextGroup, 2200); // Every 3 seconds
                }

                // Apply the synchronized transition to both galleries
                handleSynchronizedGalleries('.gallery');
            });
        </script>

    </body>
</html>
