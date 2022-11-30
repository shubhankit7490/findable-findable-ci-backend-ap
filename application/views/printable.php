
  <div class="clearfix container">
    <header>
      <div class="name">
        <?php echo $firstname . ' ' . $lastname; ?>
      </div>
      <div class="contact">
        <span class="contact-item phone">
        <?= $phone_number ?> 
        </span>
        <span class="contact-item"> • </span>
        <a href="mailto:<?= $email ?>" class="contact-item email">
        <?= $email ?>
        </a>
        <span class="contact-item"> • </span>
        <span class="contact-item city">
          <?php 
            $address = [ $city ];
            if (!empty($state)) {
                array_push($address, $state);
            }
            array_push($address, $country);

            $address = implode( ', ', $address);
            echo $address;
            ?>
        </span>
      </div>
    </header>
    <article>
      <div class="about_me row">
        <span>About me:</span>
        <div><?= $about_me ?></div>
      </div>

      <?php if (!empty($positions)): ?>
      <section class="section experience clearfix">
        <h2>Experience</h2>

        <?php foreach ($positions as $exp): ?>
        <div class="content">
          <table>
            <tr>
              <td class="company_name">
                <span class="company_name_text"><?= $exp['company']['name']?></span>,
                <?php
                  $company_location = [
                    $exp['location']['city_name']
                  ];
                  if (!empty($exp['location']['state_short_name'])) {
                    array_push($company_location, $exp['location']['state_short_name']);
                  }
                  array_push($company_location, $exp['location']['country_short_name_alpha_3']);
                  $company_location = implode(', ', $company_location);

                  echo $company_location
                  ?>
              </td>
              <td class="date">
              <?php
                echo date('M Y', strtotime($exp['from']));
                ?> - <?php
                if ($exp['current'] == 0) {
                  echo date('M Y', strtotime($exp['to']));
                } else {
                    echo 'Present';
                }
              ?>
              </td>
            </tr>
          </table>
          <div class="company_position">
            <span class="company_position_title"><?= $exp['job_title'] ?></span>
            <ul class="company_pos_ul">
              <?php foreach ($exp['areas_of_focus'] as $res): ?>

              <li class="company_pos_li"><span class="company_pos_p"> • </span><?= $res['name'] ?></li>
              
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endforeach; ?>

      </section>
      <?php endif; ?>

      <?php if (!empty($schools)): ?>
      <section class="section education clearfix">
        <h2>Education</h2>
        
        <?php
          $j = 0;
          foreach ($schools as $edu): ?>
        <div class="content">
          <table>
            <tr>
              <td class="education_name">
                <span class="education_name_text"><?= $edu['name'] ?></span>
              </td>
              <td>
                <?php if ($j == 0): ?>
                Most Recent
                <?php
                  $j = 1;
                  endif;
                  ?>
              </td>
            </tr>
          </table>

          <?php foreach ($edu['fields'] as $fld): ?>
          <table class="education_points">
            <tr class="edu_point">
              <td>•</td>
              <td class="edu_p_desc"><?= $fld['name'] ?></td>
              <td class="edu_p_level"><?= $edu['level']['name'] ?></td>
              <td class="edu_p_time">|
                <?php 
                  $d1 = new DateTime($edu['from']);
                  $d2 = new DateTime($edu['to']);
                  
                  $interval = $d2->diff($d1);

                  if ($interval->y == 1 && $interval->m == 0) {
                    echo $interval->format('%y Year');
                  } else
                  if ($interval->y > 1 && $interval->m == 0)  {
                    echo $interval->format('%y Years');
                  }  else
                  if ($interval->y == 1 && $interval->m == 1) {
                    echo $interval->format('%y Year %m Month');
                  } else
                  if ($interval->y != 0 && $interval->m == 1) {
                    echo $interval->format('%y Years %m Month');
                  } else
                  if ($interval->y != 0 && $interval->m != 0) {
                    echo $interval->format('%y Years %m Months');
                  } else
                  if ($interval->y == 0 && $interval->m == 1) {
                    echo $interval->format('%m Month');
                  } else
                  if ($interval->y == 0 && $interval->m > 1) {
                    echo $interval->format('%m Months');
                  }                  
                  ?>
              </td>
            </tr>
          </table>
          <?php endforeach; ?>

        </div>
        <?php endforeach; ?>

      </section>
      <?php endif; ?>

      <?php if (!empty($skills)): ?>
      <section class="section skills clearfix">
        <h2>Skills</h2>

        <table class="expertise">
          <?php if (!empty($skills['expert'])): ?>
          <tr>
            <td>
              <span class="expert">Expert:</span>
            </td>
            <td>

              <?php
              $i = 0;
              foreach ($skills['expert'] as $expert): ?>
                <?php if ($i === 0): ?>
                  <?php 
                    $i = $i + 1;
                    echo $expert;
                    ?>
                <?php elseif ($i > 0): ?>
                • <?= $expert ?>
                <?php endif; ?>
              <?php endforeach; ?>

            </td>
          </tr>
          <?php endif; ?>

          <?php if (!empty($skills['pretty_good'])): ?>
          <tr>
            <td>
              <span class="pretty_good">Pretty Good:</span>
            </td>
            <td>

              <?php
              $i = 0;
              foreach ($skills['pretty_good'] as $good): ?>
                <?php if ($i === 0): ?>
                  <?php 
                    $i = $i + 1;
                    echo $good;
                    ?>
                <?php elseif ($i > 0): ?>
                • <?= $good ?>
                <?php endif; ?>
              <?php endforeach; ?>

            </td>
          </tr>
          <?php endif; ?>

          <?php if (!empty($skills['basic'])): ?>
          <tr>
            <td>
              <span class="basic">Basic:</span>
            </td>
            <td>

              <?php
              $i = 0;
              foreach ($skills['basic'] as $basic): ?>
              <?php if ($i === 0): ?>
                <?php 
                  $i = $i + 1;
                  echo $basic;
                  ?>
              <?php elseif ($i > 0): ?>
              • <?= $basic ?>
              <?php endif; ?>
              <?php endforeach; ?>

            </td>
          </tr>
          <?php endif; ?>

        </table>
        <?php endif; ?>

        <?php if (!empty($traits)): ?>
        <div class="traits">
          <span>Personal Traits:</span>

          <?php
            $i = 0;
            foreach ($traits as $trait): ?>
            <?php if ($i === 0): ?>
              <?php 
                $i = $i + 1;
                echo $trait['name'];
                ?>
            <?php elseif ($i > 0): ?>
            • <?= $trait['name'] ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($languages)): ?>
        <div class="languages">
          <span>Languages:</span>

          <table>
            <?php if (!empty($languages['pro'])): ?>

            <tr>
              <td class="pro">Pro:</td>
              <td>
                <?php
                  $i = 0;
                  foreach ($languages['pro'] as $language): ?>
                  <?php if ($i === 0): ?>
                    <?php 
                      $i = $i + 1;
                      echo $language;
                      ?>
                  <?php elseif ($i > 0): ?>
                  • <?= $language ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              </td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($languages['good'])): ?>
            <tr>
              <td class="good">Good:</td>
              <td>
                <?php
                  $i = 0;
                  foreach ($languages['good'] as $language): ?>
                  <?php if ($i === 0): ?>
                    <?php 
                      $i = $i + 1;
                      echo $language;
                      ?>
                  <?php elseif ($i > 0): ?>
                  • <?= $language ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              </td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($languages['basic'])): ?>
            <tr>
              <td class="l_basic">Basic:</td>
              <td>
                <?php
                  $i = 0;
                  foreach ($languages['basic'] as $language): ?>
                  <?php if ($i === 0): ?>
                    <?php 
                      $i = $i + 1;
                      echo $language;
                      ?>
                  <?php elseif ($i > 0): ?>
                  • <?= $language ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              </td>
            </tr>
            <?php endif; ?>
          </table>

        </div>
        <?php endif; ?>
      </section>
  
    </article>
    <style>
    html, body, div, span, applet, object, iframe,
    h1, h2, h3, h4, h5, h6, p, blockquote, pre,
    a, abbr, acronym, address, big, cite, code,
    del, dfn, em, img, ins, kbd, q, s, samp,
    small, strike, strong, sub, sup, tt, var,
    b, u, i, center,
    dl, dt, dd, ol, ul, li,
    fieldset, form, label, legend,
    table, caption, tbody, tfoot, thead, tr, th, td,
    article, aside, canvas, details, embed, 
    figure, figcaption, footer, header, hgroup, 
    menu, nav, output, ruby, section, summary,
    time, mark, audio, video {
      margin: 0;
      padding: 0;
      border: 0;
      font-size: 100%;
      font: inherit;
      vertical-align: baseline;
    }

    article, aside, details, figcaption, figure, 
    footer, header, hgroup, menu, nav, section {
      display: block;
    }
    body {
      line-height: 1;
    }
    ol, ul {
      list-style: none;
    }
    blockquote, q {
      quotes: none;
    }
    blockquote:before, blockquote:after,
    q:before, q:after {
      content: '';
      content: none;
    }
    table {
      border-collapse: collapse;
      border-spacing: 0;
    }

    .left {
      float: left;
    }

    .right {
      float: right;
    }

    .pointer {
      cursor: pointer;
    }

    .clearfix:after {
      clear: both;
      content: ".";
      display: block;
      height: 0;
      visibility: hidden;
    }
    .clearfix {
      display: inline-block;
    }
    .clearfix {
        display: block;
    }

    body {
      width: 100%;
      font-family: Arial, Helvetica, sans-serif;
    }

    div.container {
      width: 763px;
      min-height: 1000px;
      background-color: #ffffff;
      margin: 0px auto;
      padding: 15px;
    }

    header {
      width: 100%;
      height: 100px;
      background-color: #13345F;
    }
    
    .row {
      margin-top: 14px;
    }

    article {
      padding: 0px 5px;
    }

    .name {
      padding-top: 15px;
      padding-bottom: 15px;
      font-size: 27px;
      color: #f1f1f1;
      text-align: center;
      background: url('https://www.findable.co/assets/images/logo.png');
      background-repeat: no-repeat;
      background-size: 300px 66px;
      background-position: 8px 9px;
    }

    .contact {
      background:  #31517A;
      text-align: center;
      height: 29px;
      padding-top: 14px;
    }

    .contact-item {
      margin: 0 5px;
      color: #f1f1f1;
    }

    .about_me {
      max-height: 150px;
    }
    .about_me span {
      font-weight: bold;
    }

    .about_me div {
      padding: 3px 9px;
    }

    .section {
      padding: 10px 0px;
    }

    .section h2 {
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      background:  #31517A;
      color: #e9e9e9;
      padding: 5px 0px;
      border-top:  2px solid #13345F;
      border-bottom:  2px solid #13345F;
    }

    .experience {
      max-height: 300px;
    }

    td.date {
      color: #666666;
      font-size: 15px;
    }

    .content {
      margin-top: 12px;
    }

    .skills {
      max-height: 300px;
    }

    td.company_name {
      width: 440px;
      min-width: 440px;
    }
    td.education_name {
      width: 521px;
      min-width: 521px;
    }


    span.company_name_text, span.education_name_text {
      font-size: 24px;
      font-weight: bold;
      padding: 0;
    }

    span.company_position_title {
      font-size: 16px;
      font-weight: bold;
    }


    .company_position {
      padding-left: 20px;
    }

    li.company_pos_li {
      margin: 6px 0px 6px 12px;
    }

    .company_pos_p {
      padding: 5px 0px;
    }

    .education_name span {
      font-size: 24px;
      font-weight: bold;
    }

    table {
      margin: 5px;
    }

    td {
      padding: 5px;
    }

    td.edu_p_desc {
      width: 360px;
      min-width: 360px;
      font-size: 18px;
    }

    td.edu_p_level {
      width: 200px;
      min-width: 200px;
      font-size: 18px;
    }

    td.edu_p_time {
      width: 180px;
      min-width: 180px;
      font-size: 18px;
    }

    .expertise, .traits, .languages {
      margin-top: 15px;
    }

    .expertise span, .traits span, .languages span {
      font-weight: bold;
    }

    .expertise div {
      padding-top: 15px;
    }

    span.expert {
      margin-right: 42px;
      font-weight: bold;
    }
    
    span.pretty_good {
      font-weight: bold;
    }

    span.basic {
      margin-right: 50px;
      font-weight: bold;
    }

    .languages div div {
      margin: 5px;
    }
    
    td.pro, td.good, td.l_basic {
      font-weight: bold;
    }
  </style>