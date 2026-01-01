<?php
$pageTitle = "About Us";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link rel="stylesheet" href="bootstrap3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="datatables/datatables.min.css">
    <script src="jQuery-3.3.1/jquery-3.3.1.min.js"></script>
    <script src="datatables/datatables.min.js"></script>
    <script src="bootstrap3.3.7/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
            background: url('img/about photos/bg.jpeg') no-repeat center center fixed; /* Set the background image */
            background-size: cover; /* Cover the entire viewport */
            color: var(--text-dark);
            font-family: "Raleway", sans-serif;
            min-height: 100vh;
            position: relative; /* Position relative for overlay */
        }

        /* Overlay for faded effect */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7); /* White overlay with 70% opacity */
            z-index: 1; /* Ensure overlay is above the background */
        }

        header, footer {
            padding: 2rem;
            background-color: var(--white);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            position: relative; /* Position relative for z-index */
            z-index: 2; /* Ensure header/footer is above the overlay */
        }

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
        .header {
            background-color: #f8f9fa;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .nav-icons {
            display: flex;
            align-items: center;
            padding-right: 80px;
            gap: 10px;
        }

        .logo {
            height: 60px;
        }        
        .nav-icons button {
            padding: 6px 10px;
            font-size: 16px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .nav-icons button i {
            font-size: 25px;
        }

        .site-footer {
            background-color: #f8f9fc;
            padding: 40px 20px 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #444;
        }

        .site-footer h5 {
            color: #1a237e;
            margin-bottom: 15px;
        }

        .site-footer a {
            color: #1a73e8;
            text-decoration: none;
        }

        .site-footer a:hover {
            text-decoration: underline;
            color: #0d47a1;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .footer-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .footer-content p {
            margin-bottom: 5px;
        }

        .socials {
            font-size: 28px;
        }

        .member-card img {
            width: 150px; /* Set a fixed width for the images */
            height: auto; /* Maintain aspect ratio */
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
        .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 35px 25px;
            width: 100%;
            box-sizing: border-box;
            z-index: 2;
            background-color: rgba(255,255,255,0.2);
        }
        .nav-list {
            list-style: none;
            display: flex;
            gap: 35px;
            margin: 0;
            padding: 0;
            z-index: 2;
        }
        .nav-link {
            text-decoration: none;
            color: black;
            font-size: 18px;
            font-weight: 500;
            padding-bottom: 4px;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
            z-index: 2;
        }
        .nav-link:hover {
            color: #1a73e8;
            border-bottom: 2px solid #1a73e8;
            text-decoration: none;
        }
        .nav-link.active {
            border-bottom: 2px solid #1a73e8;
            color: #1a73e8;

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

<header class="header">
    <div>
        <img src="img/pshsLogo.png" alt="Banner" class="logo" style="height: 60px;">
        <img src="img/banner.png" alt="Banner" class="logo" style="height: 60px;">
    </div>    
    <div class="nav-icons">
        <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="popover" title="Notifications" data-html="true" data-content='<div> You have <span class="text-danger">3</span> new requests.<br><a>Go to requests page</a></div>'>
            <i class="bi bi-bell-fill"></i>
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" data-toggle="popover" title="Profile" data-html="true" data-content='<a href="logout.php">Logout</a>'>
            <i class="bi bi-person-circle"></i>
        </button>
    </div>
</header>
<nav class="main-nav d-flex justify-content-between align-items-center p-3">
    <ul class="nav-list d-flex gap-3 list-unstyled">
        <li><a href="home.php" class="nav-link ">Home</a></li>
        <li><a href="labs.php" class="nav-link">Labs</a></li>
        <li><a href="requests.php" class="nav-link">Requests</a></li>
        <li><a href="reports.php" class="nav-link">Reports</a></li>
        <li><a href="reports.php" class="nav-link active">About Us</a></li>
    </ul>
</nav>


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
            <img src="img/about photos/rojan.jpeg" alt="Rojan Joefel C. Dumlao">
            <h3>Rojan Joefel C. Dumlao</h3>
            <h4>Backend Engineer. Keeps the engines running with logic and power.</h4>
        </div>
        <div class="member-card">
            <img src="img/about photos/ben.jpeg" alt="Christian Benedict U. Soy">
            <h3>Christian Benedict U. Soy</h3>
            <h4>Content Strategist. Makes ideas come to life with words and clarity.</h4>
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

<footer class="site-footer">
    <div class="container">
        <div class="row footer-content">
            <div class="col-md-4">
                <h5>Philippine Science High School</h5>
                <p>Ilocos Region Campus</p>
                <p><i class="bi bi-geo-alt-fill"></i> San Ildefonso, Ilocos Sur, Philippines</p>
            </div>

            <div class="col-md-4 text-center">
                <img src="img/pshsLogo.png" alt="PSHS IRC Logo" class="footer-logo">
            </div>

            <div class="col-md-4 text-right socials">
                <h5>Follow Us</h5>
                <a href="#" class="text-decoration-none me-2"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-decoration-none me-2"><i class="bi bi-twitter-x"></i></a>
                <a href="#" class="text-decoration-none"><i class="bi bi-envelope-fill"></i></a>
            </div>
        </div>
        <div class="text-center mt-3">
            <small>&copy; <?= date("Y") ?> PSHS IRC. All rights reserved.</small>
        </div>
    </div>
</footer>

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
