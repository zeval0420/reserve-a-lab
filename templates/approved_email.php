<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Science Lab Request System</title>
    <link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
      /* ======= Global ======= */
      html, body {
        margin: 0;
        padding: 0;
        height: 100%;
      }

      body {
        font-family: 'Poppins', 'SF Pro Display', sans-serif;
        background: linear-gradient(135deg, #eaeaea, #f5f5f5, #fdfdfd);
        display: flex;
        justify-content: center;
        align-items: center;
        color: #111;
        overflow: hidden; /* prevents slight scrollbars */
      }

      /* ======= Glass Container ======= */
      .container {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 28px;
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        border: 2px solid rgba(255, 255, 255, 0.55);
        box-shadow:
          0 25px 60px rgba(0, 0, 0, 0.25),
          inset 0 1px 1px rgba(255, 255, 255, 0.6),
          inset 0 0 30px rgba(255, 255, 255, 0.25);
        max-width: 620px;
        width: 90%;
        overflow: hidden;
        transition: all 0.3s ease;
        text-align: left;
      }

      .container:hover {
        box-shadow:
          0 35px 70px rgba(0, 0, 0, 0.3),
          inset 0 1px 1px rgba(255, 255, 255, 0.7);
        transform: translateY(-3px);
      }

      /* ======= Header ======= */
      .header {
        text-align: center;
        padding: 35px 25px 25px 25px;
        color: #000;
        background: rgba(255, 255, 255, 0.18);
        border-bottom: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: inset 0 -1px 8px rgba(255, 255, 255, 0.15);
      }

      .header h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
      }

      /* ======= Main Content ======= */
      .content {
        padding: 28px 40px 35px 40px;
        background: rgba(255, 255, 255, 0.25);
        border-top: 1px solid rgba(255, 255, 255, 0.3);
      }

      .content h4 {
        margin: 0;
        font-weight: 600;
        font-size: 17px;
        color: #222;
      }

      .content p {
        margin-top: 10px;
        color: #333;
        font-size: 15px;
        line-height: 1.6;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.6);
      }

      /* ======= Status Tag ======= */
      .status {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        background: rgba(255, 255, 255, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow:
          inset 0 0 8px rgba(255, 255, 255, 0.8),
          0 1px 6px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(10px);
        color: #111;
        margin-left: 6px;
      }

      /* ======= Details Box ======= */
      .details {
        margin-top: 25px;
        border-radius: 20px;
        padding: 20px 25px;
        background: rgba(255, 255, 255, 0.28);
        border: 1px solid rgba(255, 255, 255, 0.55);
        box-shadow:
          0 6px 20px rgba(0, 0, 0, 0.05),
          inset 0 0 20px rgba(255, 255, 255, 0.3);
      }

      .details p {
        margin: 10px 0;
        font-size: 14px;
        color: #222;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.5);
      }

      .details span {
        font-weight: 600;
        color: #111;
      }

      /* ======= Button ======= */
      .button-wrapper {
        text-align: center;
        margin-top: 35px;
      }

      .button-wrapper a {
        display: inline-block;
        padding: 14px 38px;
        border-radius: 35px;
        background: linear-gradient(145deg, rgba(30,30,30,0.85), rgba(10,10,10,0.9));
        border: 2px solid rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        color: #f1f1f1;
        box-shadow:
          0 12px 28px rgba(0, 0, 0, 0.4),
          inset 0 0 10px rgba(255, 255, 255, 0.3);
        transition: all 0.35s ease;
        backdrop-filter: blur(20px);
        position: relative;
        overflow: hidden;
      }

      .button-wrapper a::before {
        content: "";
        position: absolute;
        top: 0;
        left: -80%;
        width: 50%;
        height: 100%;
        background: linear-gradient(120deg, rgba(255,255,255,0.6), rgba(255,255,255,0));
        transform: skewX(-25deg);
        transition: 0.7s;
        opacity: 0;
      }

      .button-wrapper a:hover::before {
        left: 130%;
        opacity: 1;
      }

      .button-wrapper a:hover {
        background: linear-gradient(145deg, rgba(50,50,50,1), rgba(15,15,15,1));
        transform: scale(1.07);
        border-color: rgba(255, 255, 255, 0.9);
        box-shadow:
          0 16px 40px rgba(0, 0, 0, 0.5),
          inset 0 0 15px rgba(255, 255, 255, 0.5);
        color: #fff;
      }

      .button-wrapper a:active {
        transform: scale(0.98);
        box-shadow:
          0 6px 15px rgba(0, 0, 0, 0.35),
          inset 0 0 8px rgba(255, 255, 255, 0.4);
      }

      /* ======= Footer ======= */
      .footer {
        text-align: center;
        font-size: 12px;
        color: #666;
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.5);
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        box-shadow: inset 0 1px 6px rgba(255, 255, 255, 0.15);
      }

      @media (max-width: 640px) {
        body {
          align-items: flex-start;
          padding: 20px 0;
          overflow-y: auto;
        }
        .content {
          padding: 25px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <!-- Header -->
      <div class="header">
        <h1>Science Lab Request System</h1>
      </div>

      <!-- Main Content -->
      <div class="content">
        <h4>Dear [NAME],</h4>
        <p>Your request to access the Science Laboratory has been
          <span class="status">[STATUS]</span>.
        </p>

        <div class="details">
          <p><span>Control Number:</span> [Control Number]</p>
          <p><span>Facility:</span> [Facility]</p>
          <p><span>Grade & Section:</span> [Grade & Section]</p>
          <p><span>Subject:</span> [Subject]</p>
          <p><span>Concurrent Topic:</span> [Concurrent Topic]</p>
          <p><span>Schedule:</span> [Schedule]</p>
        </div>

        <div class="button-wrapper">
          <a href="https://pshsirc.online/scilab/requests.php">Go to Login Page</a>
        </div>
      </div>

      <!-- Footer -->
      <div class="footer">
        This is an auto-generated email. Please do not reply to this message.
      </div>
    </div>
  </body>
</html>