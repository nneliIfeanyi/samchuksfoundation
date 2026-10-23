-- Keep image paths project-relative; the frontend prepends EVENTS_ASSETS_BASE_URL.
USE samchuksfoundation;

INSERT INTO events
    (title, slug, category, description, event_date, programme_year, participants, duration, cover_image, gallery_json, status, featured)
VALUES
    (
        'Community Outreach',
        'orphanage',
        'Community Outreach',
        'On Valentine''s Day 2022, the Samchuks Foundation visited an orphanage home, bringing love and joy to the children. Foundation members spent time with the children, sharing stories, playing games, and distributing gifts. The visit was a heartwarming expression of the foundation''s commitment to supporting vulnerable children and spreading love and kindness within the community. It was a special Valentine''s Day for the children, filled with smiles and a sense of belonging.',
        NULL,
        'Feb. 2022',
        '3+ Orphanage homes',
        'Annually',
        'assets/img/new/bg-about.jpeg',
        JSON_ARRAY('assets/img/new/bg-about.jpeg'),
        'published',
        0
    ),
    (
        'Community Outreach',
        'outreach',
        'Community Outreach',
        'In 2023, the Samchuks Foundation continued its annual tradition of visiting an orphanage home, demonstrating their ongoing commitment to the well-being of the children. The visit provided an opportunity for foundation members to connect with the children, share in activities, and offer support. This annual event serves as a tangible expression of the foundation''s love and care, bringing joy and positive impact to the lives of the children at the orphanage.',
        NULL,
        'Jan. 2023',
        '3+ Orphanage homes',
        'Annually',
        'assets/img/new/orph.jpeg',
        JSON_ARRAY('assets/img/new/orph.jpeg'),
        'published',
        0
    ),
    (
        'Fashion Design Skill Acquisation',
        'fashion',
        'Skill Acquisation',
        'A 6-month intensive fashion designing training program equipped 50 beneficiaries with valuable skills, enabling them to generate income. The training covered various aspects of fashion design, from sketching and pattern making to sewing and garment construction. Participants learned to create their own designs and tailor outfits, opening up opportunities for self-employment or employment in the fashion industry. At the end of the training, the beneficiaries were fully empowered with sewing machine and other accessories. This initiative has not only empowered individuals but also contributed to the growth of the local fashion community.',
        NULL,
        'Nov. 2023 - April 2024',
        '50+ individuals',
        '6 Months',
        'assets/img/new/fash2.jpeg',
        JSON_ARRAY('assets/img/new/fash2.jpeg', 'assets/img/new/event1.jpeg', 'assets/img/new/mil1.jpeg'),
        'published',
        1
    ),
    (
        'Community Outreach',
        'orphanage-2024',
        'Community Outreach',
        'The Samchuks Foundation made its annual visit to a local orphanage in 2024, bringing cheer and demonstrating their continued dedication to the children residing there. The visit included interaction with the children through games, storytelling, and the distribution of gifts. This yearly tradition underscores the Foundation''s commitment to supporting vulnerable children and spreading love and kindness within the community.',
        NULL,
        'Sept. 2024',
        '5+ Orphanage homes',
        'Annually',
        'assets/img/new/orph2.jpeg',
        JSON_ARRAY('assets/img/new/orph2.jpeg'),
        'published',
        1
    ),
    (
        'Shoe Making Skill Acquisation',
        'shoe',
        'Skill Acquisation',
        'A total of fifty individuals successfully completed a comprehensive six-month shoe-making training program, gaining skills that are now enabling them to generate income and also empowered with necessary tools. The intensive training covered the full shoemaking process, from conceptual design and pattern creation to the intricacies of construction, stitching, and finishing. Participants developed practical expertise, preparing them to pursue self-employment by establishing their own small businesses or to seek opportunities within the footwear industry. The program has demonstrably empowered these individuals to achieve financial independence and is expected to have a positive ripple effect on their communities.',
        NULL,
        'Nov. 2023 - April 2024',
        '50+ individuals',
        '6 Months',
        'assets/img/new/shoe1.jpeg',
        JSON_ARRAY('assets/img/new/shoe1.jpeg', 'assets/img/new/event2.jpeg', 'assets/img/new/shoe2.jpeg'),
        'published',
        1
    ),
    (
        'Baking And Catering Skill Acquisation',
        'baking',
        'Skill Acquisation',
        'Sixty beneficiaries successfully completed a six-month training program focused on baking and catering. The comprehensive curriculum covered a wide range of skills, from basic baking techniques, commercial international and local dishes preparations and recipe development to advanced culinary arts and catering management. Upon completion of the program, each participant received a set of essential baking tools, empowering them to immediately begin generating income. This investment in both training and equipment has enabled these individuals to launch their own baking or catering businesses, creating new economic opportunities for themselves and their communities.',
        NULL,
        'Nov. 2023 - April 2024',
        '60+ individuals',
        '6 Months',
        'assets/img/new/event3.jpeg',
        JSON_ARRAY('assets/img/new/event3.jpeg', 'assets/img/new/mil3.jpeg'),
        'published',
        0
    ),
    (
        'Furniture Making Skill Acquisation',
        'furniture',
        'Skill Acquisation',
        'It was a six-month furniture making training program provided 50 community members with the skills and tools they need to generate income. The intensive training covered the full spectrum of furniture construction, from design principles and material selection to woodworking techniques, joinery, finishing, and upholstery. Crucially, each participant received a set of essential tools upon completion of the program. This practical support, coupled with the comprehensive training, has empowered the beneficiaries to establish their own furniture-making businesses or seek employment in the industry, creating new economic opportunities within the community.',
        NULL,
        'Nov. 2023 - April 2024',
        '50+ individuals',
        '6 Months',
        'assets/img/new/funny1.jpeg',
        JSON_ARRAY('assets/img/new/funny1.jpeg', 'assets/img/new/funny2.jpeg'),
        'published',
        0
    ),
    (
        'Animal Farming Skill Acquisation',
        'animal',
        'Skill Acquisation',
        'A six-month animal farming training program equipped 50 beneficiaries with the skills and resources needed to generate income. The program provided comprehensive instruction covering diverse aspects of animal husbandry, from basic care and nutrition to advanced breeding techniques, disease prevention, and farm business management. Participants also received essential tools and resources relevant to their specific animal farming interests upon completion of the training. This combination of knowledge and practical support has enabled the beneficiaries to establish or improve their own animal farming operations, leading to increased income and enhanced livelihoods.',
        NULL,
        'Nov. 2023 - April 2024',
        '50+ individuals',
        '6 Months',
        'assets/img/new/ani.jpeg',
        JSON_ARRAY('assets/img/new/ani.jpeg'),
        'published',
        0
    )
ON DUPLICATE KEY UPDATE slug = VALUES(slug);
