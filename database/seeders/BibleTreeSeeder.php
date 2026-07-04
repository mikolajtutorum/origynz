<?php

namespace Database\Seeders;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Seeder;

class BibleTreeSeeder extends Seeder
{
    public function run(): void
    {
        if (FamilyTree::where('name', 'Biblical Tree')->exists()) {
            $this->command->info('Biblical Tree already seeded, skipping.');

            return;
        }

        $user = User::firstOrFail();

        $tree = FamilyTree::create([
            'user_id' => $user->id,
            'name' => 'Biblical Tree',
            'description' => 'Historically attested figures from the Hebrew Bible, beginning with Omri, King of Israel (c. 885–874 BCE) — the earliest independently verified person in the biblical genealogy, attested by the Mesha Stele and Assyrian records. The chain runs from Omri through 28 generations to Jesus of Nazareth.',
            'home_region' => 'Ancient Israel & Judah',
            'privacy' => 'public',
            'global_tree_enabled' => true,
        ]);

        $this->createSources($tree, $user);
        $persons = $this->createPersons($tree, $user);

        $tree->update(['owner_person_id' => $persons['omri']->id]);

        $this->createRelationships($tree, $persons);

        $this->command->info('Biblical Tree seeded — '.count($persons).' persons, Omri → Jesus in 28 generations.');
    }

    private function createSources(FamilyTree $tree, User $user): void
    {
        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Mesha Stele (Moabite Stone)',
            'author' => 'King Mesha of Moab',
            'publication_facts' => 'c. 840 BCE; discovered 1868; Louvre Museum, Paris',
            'repository' => 'Louvre Museum, Paris (AO 5066)',
            'text' => 'Basalt inscription by Mesha, King of Moab. Mentions "Omri, King of Israel" and that Israel had oppressed Moab during his reign. Provides the earliest extra-biblical attestation of the Israelite monarchy.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Kurkh Monolith (Shalmaneser III Annals)',
            'author' => 'Scribes of Shalmaneser III of Assyria',
            'publication_facts' => 'c. 853 BCE; British Museum, London (BM 118884)',
            'repository' => 'British Museum, London',
            'text' => 'Assyrian annals recording the Battle of Qarqar (853 BCE). Names "Ahab the Israelite" (A-ha-ab-bu Sir-\'a-la-aa) as commanding 2,000 chariots and 10,000 soldiers — the largest chariot force in the coalition against Shalmaneser III.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Black Obelisk of Shalmaneser III',
            'author' => 'Scribes of Shalmaneser III of Assyria',
            'publication_facts' => 'c. 841 BCE; British Museum, London (BM 118885)',
            'repository' => 'British Museum, London',
            'text' => 'Black limestone obelisk depicting tribute payments. Panel II shows Jehu labelled "Ia-ú-a, son of Omri" (mār Ḫumri), bowing before Shalmaneser III — the earliest contemporary visual depiction of a named Israelite.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Tiglath-Pileser III Annals & Ahaz Bulla',
            'author' => 'Scribes of Tiglath-Pileser III of Assyria',
            'publication_facts' => 'c. 734–732 BCE; British Museum & Israel Museum',
            'text' => 'Tiglath-Pileser III\'s annals name "Jehoahaz of Judah" among tribute-paying kings. An inscribed clay bulla reading "Ahaz son of Yehotam, King of Judah" provides a personal seal of the king himself.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Taylor Prism (Sennacherib\'s Third Campaign)',
            'author' => 'Scribes of Sennacherib of Assyria',
            'publication_facts' => 'c. 691 BCE; British Museum, London (BM 91032)',
            'repository' => 'British Museum, London',
            'text' => 'Clay prism recording Sennacherib\'s campaigns. Names "Hezekiah the Judahite" (Ha-za-qi-a-ú Ia-ú-da-a-a) and describes besieging Jerusalem in 701 BCE — corroborates 2 Kings 18–19 closely.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Esarhaddon\'s Prism (Vassal Treaty)',
            'author' => 'Scribes of Esarhaddon of Assyria',
            'publication_facts' => 'c. 676 BCE; multiple museum collections',
            'text' => 'Lists "Manasseh, king of Judah" (Ma-na-si-i šar Ia-ú-di) among 22 kings compelled to supply building materials for Nineveh. Ashurbanipal\'s later annals also name him.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Babylonian Chronicle BM 21946 & Weidner Ration Tablets',
            'author' => 'Babylonian royal scribes',
            'publication_facts' => 'Records events 605–594 BCE; British Museum, London',
            'text' => 'The Babylonian Chronicle records the siege of Jerusalem in 597 BCE and the capture of "its king." The Weidner Tablets (~595–570 BCE) list "Yaukin, king of the land of Yahud" receiving oil rations — a direct record of Jehoiachin in Babylonian captivity.',
            'quality' => 5,
            'source_type' => 'Archaeological inscription',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Books of Kings (Hebrew Bible)',
            'publication_facts' => '1 Kings 16 – 2 Kings 25; compiled c. 7th–6th century BCE',
            'text' => 'Primary biblical source for the reigns of the Israelite and Judaean kings from Omri through the fall of Jerusalem. Contains synchronised regnal chronologies for both kingdoms.',
            'quality' => 3,
            'source_type' => 'Ancient text',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Gospel of Matthew — Genealogy of Jesus',
            'publication_facts' => 'Matthew 1:1–17; composed c. 80–90 CE',
            'text' => 'Traces Jesus\'s legal paternity through Joseph: Abraham → David → … → Jehoram → (skipping Ahaziah, Joash, Amaziah) → Uzziah → … → Josiah → (skipping Jehoahaz & Jehoiakim) → Jeconiah → Shealtiel → Zerubbabel → Abiud → Eliakim → Azor → Zadok → Achim → Eliud → Eleazar → Matthan → Jacob → Joseph → Jesus.',
            'quality' => 3,
            'source_type' => 'Ancient text',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Josephus, Antiquities of the Jews',
            'author' => 'Flavius Josephus',
            'publication_facts' => 'c. 93–94 CE; Books VIII–IX, XVIII',
            'text' => 'Josephus names Ethbaal (Ithobaal I) as father of Jezebel (Ant. VIII.13.1), corroborating 1 Kings 16:31. His partially-interpolated Testimonium Flavianum (Ant. 18.3.3) is accepted by most scholars as containing a genuine first-century reference to Jesus.',
            'quality' => 3,
            'source_type' => 'Ancient text',
        ]);

        Source::create([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'title' => 'Tacitus, Annals',
            'author' => 'Publius Cornelius Tacitus',
            'publication_facts' => 'Annals 15.44; c. 116 CE',
            'text' => 'Records that "Christus, from whom the name had its origin, suffered the extreme penalty during the reign of Tiberius at the hands of one of our procurators, Pontius Pilatus." Independent Roman attestation of Jesus\'s execution.',
            'quality' => 5,
            'source_type' => 'Ancient text',
        ]);
    }

    private function createPersons(FamilyTree $tree, User $user): array
    {
        $make = fn (array $data) => Person::create(array_merge([
            'family_tree_id' => $tree->id,
            'created_by' => $user->id,
            'is_living' => false,
            'exclude_from_global_tree' => false,
        ], $data));

        // ── Omri's dynasty (House of Omri / Bit-Humri) ──────────────────────

        $omri = $make([
            'given_name' => 'Omri',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 910 BCE',
            'death_date_text' => 'c. 874 BCE',
            'birth_place' => 'Kingdom of Israel',
            'death_place' => 'Tirzah, Kingdom of Israel',
            'headline' => 'King of Israel (c. 885–874 BCE); founder of the Omride dynasty',
            'notes' => "Omri founded the city of Samaria as his capital (1 Kings 16:24) and is the earliest king of Israel independently attested outside the Bible. The Mesha Stele names him directly, and the Assyrians referred to Israel as Bīt-Ḫumrī ('House of Omri') for over a century after his reign.",
        ]);

        $ahab = $make([
            'given_name' => 'Ahab',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 900 BCE',
            'death_date_text' => '853 BCE',
            'birth_place' => 'Kingdom of Israel',
            'death_place' => 'Ramoth-Gilead, Kingdom of Israel',
            'headline' => 'King of Israel (c. 874–853 BCE); named in Assyrian records',
            'notes' => "Son of Omri. The Kurkh Monolith of Shalmaneser III records 'Ahab the Israelite' as providing the largest chariot contingent (2,000) at the Battle of Qarqar (853 BCE). He married Jezebel of Sidon and was killed at the Battle of Ramoth-Gilead.",
        ]);

        $ethbaal = $make([
            'given_name' => 'Ethbaal',
            'surname' => 'of Sidon',
            'sex' => 'male',
            'birth_date_text' => 'c. 930 BCE',
            'death_date_text' => 'c. 868 BCE',
            'birth_place' => 'Phoenicia',
            'headline' => 'King of Tyre and Sidon (c. 887–856 BCE); father of Jezebel',
            'notes' => "Named in 1 Kings 16:31 as father of Jezebel. Josephus (Antiquities VIII.13.1) calls him 'King of the Tyrians and Sidonians', citing Menander of Ephesus who recorded him in Phoenician king-lists.",
        ]);

        $jezebel = $make([
            'given_name' => 'Jezebel',
            'surname' => 'of Sidon',
            'sex' => 'female',
            'birth_date_text' => 'c. 895 BCE',
            'death_date_text' => 'c. 843 BCE',
            'birth_place' => 'Sidon, Phoenicia',
            'death_place' => 'Jezreel, Kingdom of Israel',
            'headline' => 'Queen of Israel; daughter of Ethbaal of Sidon, wife of Ahab',
            'notes' => 'Phoenician princess who married Ahab as part of a political alliance. Promoted Baal worship in Israel. Her death by defenestration at Jezreel is recorded in 2 Kings 9:30–37.',
        ]);

        $ahaziah_israel = $make([
            'given_name' => 'Ahaziah',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 875 BCE',
            'death_date_text' => '852 BCE',
            'birth_place' => 'Samaria, Kingdom of Israel',
            'headline' => 'King of Israel (853–852 BCE); son of Ahab and Jezebel',
            'notes' => 'Reigned approximately one year. Died from injuries after falling through a lattice in his palace (2 Kings 1:2–17). Had no sons; succeeded by his brother Joram.',
        ]);

        $joram_israel = $make([
            'given_name' => 'Joram',
            'surname' => 'of Israel',
            'alternative_name' => 'Jehoram of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 873 BCE',
            'death_date_text' => '841 BCE',
            'birth_place' => 'Samaria, Kingdom of Israel',
            'death_place' => 'Jezreel, Kingdom of Israel',
            'headline' => 'King of Israel (852–841 BCE); last king of the House of Omri',
            'notes' => 'Son of Ahab and Jezebel; last king of the Omride dynasty. Killed by Jehu in his chariot at the plot of Naboth the Jezreelite (2 Kings 9:24).',
        ]);

        $athaliah = $make([
            'given_name' => 'Athaliah',
            'surname' => 'of Judah',
            'sex' => 'female',
            'birth_date_text' => 'c. 870 BCE',
            'death_date_text' => '835 BCE',
            'birth_place' => 'Kingdom of Israel',
            'death_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'Queen of Judah (841–835 BCE); daughter of Ahab; the link between Omri\'s line and the Davidic genealogy to Jesus',
            'notes' => "Daughter of Ahab (and likely Jezebel). Her marriage to Jehoram of Judah injected Omri's bloodline into the Davidic line — making Omri an ancestor of Jesus via Matthew's genealogy. After her son Ahaziah's death she seized the throne and reigned six years. Deposed and executed by the priest Jehoiada (2 Kings 11).",
        ]);

        // ── Kingdom of Judah — Davidic line ─────────────────────────────────

        $jehoshaphat = $make([
            'given_name' => 'Jehoshaphat',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 900 BCE',
            'death_date_text' => '848 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (c. 873–848 BCE); ally of Ahab; father of Jehoram of Judah',
            'notes' => 'King of Judah and contemporary of Ahab of Israel. Formed a close political alliance with the northern kingdom, sealed by the marriage of his son Jehoram to Athaliah (daughter of Ahab).',
        ]);

        $jehoram_judah = $make([
            'given_name' => 'Jehoram',
            'surname' => 'of Judah',
            'alternative_name' => 'Joram of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 875 BCE',
            'death_date_text' => '841 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (848–841 BCE); son of Jehoshaphat; husband of Athaliah',
            'notes' => "Son of Jehoshaphat; married Athaliah, daughter of Ahab of Israel. Named in Matthew 1:8 as an ancestor of Jesus — the verse where Omri's bloodline formally enters the Davidic genealogy.",
        ]);

        $ahaziah_judah = $make([
            'given_name' => 'Ahaziah',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 864 BCE',
            'death_date_text' => '841 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'death_place' => 'Megiddo, Kingdom of Israel',
            'headline' => 'King of Judah (841 BCE); son of Jehoram and Athaliah',
            'notes' => "Reigned less than a year. Killed by Jehu at Megiddo. His son Joash was hidden from Athaliah's purge and restored the Davidic line.",
        ]);

        $zibiah = $make([
            'given_name' => 'Zibiah',
            'surname' => 'of Beersheba',
            'sex' => 'female',
            'birth_date_text' => 'c. 865 BCE',
            'birth_place' => 'Beersheba, Kingdom of Judah',
            'headline' => 'Wife of Ahaziah of Judah; mother of Joash',
            'notes' => 'Named in 2 Kings 12:1 and 2 Chronicles 24:1. Her Judahite origin (Beersheba) contrasts with the Omride/Phoenician ancestry on the paternal side.',
        ]);

        $joash_judah = $make([
            'given_name' => 'Joash',
            'surname' => 'of Judah',
            'alternative_name' => 'Jehoash of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 842 BCE',
            'death_date_text' => '796 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (835–796 BCE); hidden from Athaliah as an infant',
            'notes' => 'Son of Ahaziah and Zibiah. Hidden in the Temple for six years by the priest Jehoiada when Athaliah seized power. Crowned at age 7, making him the youngest king of Judah. Matthew 1:8 skips the three kings Ahaziah → Joash → Amaziah and resumes the genealogy at Uzziah.',
        ]);

        $amaziah = $make([
            'given_name' => 'Amaziah',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 826 BCE',
            'death_date_text' => '767 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'death_place' => 'Lachish, Kingdom of Judah',
            'headline' => 'King of Judah (796–767 BCE); son of Joash',
            'notes' => "Son of Joash of Judah. Defeated Edom but was humiliatingly defeated by Jehoash of Israel, who breached Jerusalem's wall (2 Kings 14:11–13). Assassinated at Lachish.",
        ]);

        $uzziah = $make([
            'given_name' => 'Uzziah',
            'surname' => 'of Judah',
            'alternative_name' => 'Azariah of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 808 BCE',
            'death_date_text' => '740 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (c. 767–740 BCE); where Matthew resumes the Davidic line after skipping three kings',
            'notes' => "Son of Amaziah. Reigned ~52 years. Struck with leprosy after unlawfully burning incense in the Temple. Matthew 1:8–9 names him 'Ozias' and — skipping Ahaziah, Joash, and Amaziah — resumes the Davidic genealogy here.",
        ]);

        $jotham = $make([
            'given_name' => 'Jotham',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 772 BCE',
            'death_date_text' => '732 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (c. 740–732 BCE); son of Uzziah',
            'notes' => "Son of Uzziah; served as co-regent during his father's leprosy. Built the Upper Gate of the Temple.",
        ]);

        $ahaz = $make([
            'given_name' => 'Ahaz',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 745 BCE',
            'death_date_text' => '716 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (c. 732–716 BCE); named in Tiglath-Pileser III\'s annals and on a royal bulla',
            'notes' => "Son of Jotham. Tiglath-Pileser III's annals name him 'Jehoahaz of Judah' among tribute-paying kings (c. 734 BCE). A clay bulla reading 'Ahaz son of Yehotam, King of Judah' provides a personal seal impression.",
        ]);

        $hezekiah = $make([
            'given_name' => 'Hezekiah',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 739 BCE',
            'death_date_text' => '687 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (c. 716–687 BCE); named on Sennacherib\'s Taylor Prism',
            'notes' => "Son of Ahaz. Sennacherib's Taylor Prism (c. 691 BCE) records the siege of Jerusalem in 701 BCE, naming 'Hezekiah the Judahite.' The Siloam Tunnel inscription dates to his reign. One of the most archaeologically attested kings of Judah.",
        ]);

        $manasseh = $make([
            'given_name' => 'Manasseh',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 709 BCE',
            'death_date_text' => '643 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (c. 687–643 BCE); longest-reigning king of Judah; named in Assyrian records',
            'notes' => "Son of Hezekiah. Reigned ~55 years. Named on both Esarhaddon's prism (~676 BCE) and Ashurbanipal's records as a vassal king.",
        ]);

        $amon = $make([
            'given_name' => 'Amon',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 664 BCE',
            'death_date_text' => '641 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (643–641 BCE); son of Manasseh',
            'notes' => 'Son of Manasseh. Reigned only two years before being assassinated by his own servants (2 Kings 21:23).',
        ]);

        $josiah = $make([
            'given_name' => 'Josiah',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 648 BCE',
            'death_date_text' => '609 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'death_place' => 'Megiddo, Kingdom of Judah',
            'headline' => 'King of Judah (641–609 BCE); major religious reformer; died at Battle of Megiddo',
            'notes' => "Son of Amon. His reform movement, triggered by the discovery of a 'Book of the Law' in the Temple (~622 BCE), centralised worship in Jerusalem. Killed at Megiddo attempting to intercept Pharaoh Necho II. Matthew 1:10–11 compresses: 'Josiah begot Jeconiah', skipping Jehoahaz and Jehoiakim.",
        ]);

        $jehoahaz_judah = $make([
            'given_name' => 'Jehoahaz',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 632 BCE',
            'death_date_text' => 'c. 609 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'death_place' => 'Egypt',
            'headline' => 'King of Judah (609 BCE, 3 months); deposed by Pharaoh Necho II',
            'notes' => "Son of Josiah; ruled only three months before Pharaoh Necho II deported him to Egypt, where he died (2 Kings 23:31–34). Not in Matthew's direct genealogical line.",
        ]);

        $jehoiakim = $make([
            'given_name' => 'Jehoiakim',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 634 BCE',
            'death_date_text' => '598 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'headline' => 'King of Judah (609–598 BCE); son of Josiah; named in the Babylonian Chronicle',
            'notes' => 'Born Eliakim; renamed Jehoiakim by Pharaoh Necho II. The Babylonian Chronicle BM 21946 provides context for his submission to Nebuchadnezzar. Matthew skips him, going directly from Josiah to Jeconiah.',
        ]);

        $jehoiachin = $make([
            'given_name' => 'Jehoiachin',
            'surname' => 'of Judah',
            'alternative_name' => 'Jeconiah; Coniah',
            'sex' => 'male',
            'birth_date_text' => 'c. 616 BCE',
            'death_date_text' => 'c. 560 BCE',
            'birth_place' => 'Jerusalem, Kingdom of Judah',
            'death_place' => 'Babylon',
            'headline' => 'King of Judah (598–597 BCE); named on Babylonian Ration Tablets; "Jeconiah" in Matthew\'s genealogy',
            'notes' => "Son of Jehoiakim. Deported to Babylon by Nebuchadnezzar II in 597 BCE. The Babylonian Weidner Tablets (~595–570 BCE) list 'Yaukin, king of the land of Yahud' receiving oil rations — the most direct extra-biblical attestation of a Judahite king in exile. Released from prison c. 560 BCE. The genealogy pivots at 'the deportation to Babylon' (Matthew 1:11–12).",
        ]);

        // ── Exilic & post-exilic ─────────────────────────────────────────────

        $shealtiel = $make([
            'given_name' => 'Shealtiel',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 598 BCE',
            'death_date_text' => 'c. 545 BCE',
            'birth_place' => 'Babylon',
            'headline' => 'Son of Jehoiachin; father of Zerubbabel (Matthew 1:12)',
            'notes' => 'Named in Matthew 1:12 and 1 Chronicles 3:17 as son of Jeconiah and father of Zerubbabel.',
        ]);

        $zerubbabel = $make([
            'given_name' => 'Zerubbabel',
            'surname' => 'of Judah',
            'sex' => 'male',
            'birth_date_text' => 'c. 566 BCE',
            'death_date_text' => 'c. 510 BCE',
            'birth_place' => 'Babylon',
            'death_place' => 'Jerusalem',
            'headline' => 'Governor of Judah; led first return from Babylonian exile c. 538 BCE',
            'notes' => "Son of Shealtiel. Led the first wave of exiles back to Judah under Cyrus the Great's edict (Ezra 1–2) and began rebuilding the Temple. Named directly in Ezra, Nehemiah, Haggai, and Zechariah. The last person in the Matthean genealogy with independent historical attestation.",
        ]);

        // ── Matthew 1:13–15 — nine generations attested only in the genealogy ─

        $abiud = $make([
            'given_name' => 'Abiud',
            'surname' => 'son of Zerubbabel',
            'sex' => 'male',
            'birth_date_text' => 'c. 540 BCE',
            'headline' => 'Son of Zerubbabel (Matthew 1:13)',
            'notes' => 'Named only in Matthew 1:13. One of nine post-exilic generations unattested outside the genealogy.',
        ]);

        $eliakim_mt = $make([
            'given_name' => 'Eliakim',
            'surname' => 'son of Abiud',
            'sex' => 'male',
            'birth_date_text' => 'c. 505 BCE',
            'headline' => 'Son of Abiud (Matthew 1:13)',
            'notes' => 'Named only in Matthew 1:13.',
        ]);

        $azor = $make([
            'given_name' => 'Azor',
            'surname' => 'son of Eliakim',
            'sex' => 'male',
            'birth_date_text' => 'c. 470 BCE',
            'headline' => 'Son of Eliakim (Matthew 1:13–14)',
            'notes' => 'Named only in Matthew 1:13–14.',
        ]);

        $zadok_mt = $make([
            'given_name' => 'Zadok',
            'surname' => 'son of Azor',
            'sex' => 'male',
            'birth_date_text' => 'c. 435 BCE',
            'headline' => 'Son of Azor (Matthew 1:14)',
            'notes' => 'Named only in Matthew 1:14.',
        ]);

        $achim = $make([
            'given_name' => 'Achim',
            'surname' => 'son of Zadok',
            'sex' => 'male',
            'birth_date_text' => 'c. 400 BCE',
            'headline' => 'Son of Zadok (Matthew 1:14)',
            'notes' => 'Named only in Matthew 1:14.',
        ]);

        $eliud = $make([
            'given_name' => 'Eliud',
            'surname' => 'son of Achim',
            'sex' => 'male',
            'birth_date_text' => 'c. 365 BCE',
            'headline' => 'Son of Achim (Matthew 1:14–15)',
            'notes' => 'Named only in Matthew 1:14–15.',
        ]);

        $eleazar_mt = $make([
            'given_name' => 'Eleazar',
            'surname' => 'son of Eliud',
            'sex' => 'male',
            'birth_date_text' => 'c. 330 BCE',
            'headline' => 'Son of Eliud (Matthew 1:15)',
            'notes' => 'Named only in Matthew 1:15.',
        ]);

        $matthan = $make([
            'given_name' => 'Matthan',
            'surname' => 'son of Eleazar',
            'sex' => 'male',
            'birth_date_text' => 'c. 295 BCE',
            'headline' => 'Son of Eleazar (Matthew 1:15)',
            'notes' => 'Named only in Matthew 1:15.',
        ]);

        $jacob_mt = $make([
            'given_name' => 'Jacob',
            'surname' => 'son of Matthan',
            'sex' => 'male',
            'birth_date_text' => 'c. 100 BCE',
            'headline' => 'Son of Matthan; father of Joseph of Nazareth (Matthew 1:15–16)',
            'notes' => "Named only in Matthew 1:15–16 as the father of Joseph. The ~200-year gap between Matthan (c. 295 BCE) and Jacob (c. 100 BCE) is unexplained; most scholars consider Matthew's post-exilic list compressed.",
        ]);

        // ── Joseph, Mary, and Jesus ──────────────────────────────────────────

        $joseph = $make([
            'given_name' => 'Joseph',
            'surname' => 'of Nazareth',
            'sex' => 'male',
            'birth_date_text' => 'c. 50 BCE',
            'death_date_text' => 'before 30 CE',
            'birth_place' => 'Bethlehem (traditional) / Nazareth',
            'death_place' => 'Nazareth',
            'headline' => 'Husband of Mary; legal father of Jesus in the Matthean genealogy',
            'notes' => "Son of Jacob (Matthew 1:16). A carpenter (tektōn) from Nazareth. Matthew's genealogy runs through Joseph as the legal, Davidic father of Jesus, while explicitly noting that Jesus was 'born of Mary' (1:16).",
        ]);

        $mary = $make([
            'given_name' => 'Mary',
            'surname' => 'of Nazareth',
            'sex' => 'female',
            'birth_date_text' => 'c. 20 BCE',
            'death_date_text' => 'c. 40–60 CE',
            'birth_place' => 'Nazareth (traditional)',
            'death_place' => 'Jerusalem or Ephesus (tradition varies)',
            'headline' => 'Mother of Jesus of Nazareth; wife of Joseph',
            'notes' => "Biological mother of Jesus. Named directly in Matthew 1:16: 'Jacob begot Joseph the husband of Mary, of whom was born Jesus.'",
        ]);

        $jesus = $make([
            'given_name' => 'Jesus',
            'surname' => 'of Nazareth',
            'sex' => 'male',
            'birth_date_text' => 'c. 6–4 BCE',
            'death_date_text' => 'c. 30–33 CE',
            'birth_place' => 'Bethlehem (Matthew/Luke) or Nazareth',
            'death_place' => 'Jerusalem',
            'headline' => 'Jewish preacher executed under Pontius Pilate; terminus of the Matthean genealogy; 28 generations from Omri via Athaliah',
            'notes' => "The terminus of Matthew's 42-generation genealogy. Via Athaliah's marriage into the Davidic line, Jesus is an indirect descendant of Omri of Israel in approximately 28 generations over ~900 years. Tacitus confirms his execution under Pilate (Annals 15.44, c. 116 CE); a reconstructed core of Josephus's Testimonium Flavianum is accepted by most historians as genuine.",
        ]);

        // ── Jehu's dynasty (northern kingdom, parallel line) ────────────────

        $nimshi = $make([
            'given_name' => 'Nimshi',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 900 BCE',
            'death_date_text' => 'c. 860 BCE',
            'birth_place' => 'Kingdom of Israel',
            'headline' => 'Grandfather of Jehu',
            'notes' => 'Referenced as the grandfather of Jehu (2 Kings 9:2). No independent historical record.',
        ]);

        $jehoshaphat_nimshi = $make([
            'given_name' => 'Jehoshaphat',
            'surname' => 'ben-Nimshi',
            'sex' => 'male',
            'birth_date_text' => 'c. 875 BCE',
            'death_date_text' => 'c. 845 BCE',
            'birth_place' => 'Kingdom of Israel',
            'headline' => 'Father of Jehu; son of Nimshi',
            'notes' => 'Named in 2 Kings 9:2 and 9:14. Not to be confused with Jehoshaphat, King of Judah.',
        ]);

        $jehu = $make([
            'given_name' => 'Jehu',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 870 BCE',
            'death_date_text' => '814 BCE',
            'birth_place' => 'Kingdom of Israel',
            'death_place' => 'Samaria, Kingdom of Israel',
            'headline' => 'King of Israel (841–814 BCE); depicted on the Black Obelisk of Shalmaneser III',
            'notes' => "Panel II of the Black Obelisk of Shalmaneser III (c. 841 BCE) depicts him — labelled 'Ia-ú-a mār Ḫumrī' — prostrating himself before the Assyrian king, making this the earliest contemporary visual representation of a named Israelite ruler.",
        ]);

        $jehoahaz = $make([
            'given_name' => 'Jehoahaz',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 850 BCE',
            'death_date_text' => '798 BCE',
            'birth_place' => 'Samaria, Kingdom of Israel',
            'headline' => 'King of Israel (814–798 BCE); son of Jehu',
            'notes' => 'Son of Jehu. His reign saw heavy military pressure from Hazael of Aram-Damascus (2 Kings 13:7).',
        ]);

        $jehoash_israel = $make([
            'given_name' => 'Jehoash',
            'surname' => 'of Israel',
            'alternative_name' => 'Joash of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 830 BCE',
            'death_date_text' => '782 BCE',
            'birth_place' => 'Samaria, Kingdom of Israel',
            'headline' => 'King of Israel (798–782 BCE); son of Jehoahaz',
            'notes' => "Son of Jehoahaz. Defeated Ben-Hadad III of Aram three times and also defeated Amaziah of Judah, breaching Jerusalem's walls (2 Kings 14:13).",
        ]);

        $jeroboam_ii = $make([
            'given_name' => 'Jeroboam',
            'surname' => 'II of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 810 BCE',
            'death_date_text' => '753 BCE',
            'birth_place' => 'Samaria, Kingdom of Israel',
            'headline' => 'King of Israel (782–753 BCE); longest-reigning king of the northern kingdom',
            'notes' => 'Son of Jehoash. His 41-year reign was the peak of Israelite prosperity in the north, restoring borders to near their Solomonic extent (2 Kings 14:25–28).',
        ]);

        $zechariah_israel = $make([
            'given_name' => 'Zechariah',
            'surname' => 'of Israel',
            'sex' => 'male',
            'birth_date_text' => 'c. 790 BCE',
            'death_date_text' => '752 BCE',
            'birth_place' => 'Samaria, Kingdom of Israel',
            'headline' => 'King of Israel (753–752 BCE); last king of the House of Jehu',
            'notes' => 'Son of Jeroboam II. Reigned only six months before being publicly assassinated by Shallum ben-Jabesh (2 Kings 15:10), ending the four-generation dynasty of Jehu.',
        ]);

        return compact(
            'omri', 'ahab', 'ethbaal', 'jezebel',
            'ahaziah_israel', 'joram_israel', 'athaliah',
            'jehoshaphat', 'jehoram_judah', 'ahaziah_judah', 'zibiah',
            'joash_judah', 'amaziah', 'uzziah', 'jotham', 'ahaz',
            'hezekiah', 'manasseh', 'amon', 'josiah',
            'jehoahaz_judah', 'jehoiakim', 'jehoiachin',
            'shealtiel', 'zerubbabel',
            'abiud', 'eliakim_mt', 'azor', 'zadok_mt', 'achim',
            'eliud', 'eleazar_mt', 'matthan', 'jacob_mt',
            'joseph', 'mary', 'jesus',
            'nimshi', 'jehoshaphat_nimshi', 'jehu',
            'jehoahaz', 'jehoash_israel', 'jeroboam_ii', 'zechariah_israel'
        );
    }

    private function createRelationships(FamilyTree $tree, array $p): void
    {
        // Omri's dynasty
        $this->parentChild($tree, $p['omri'], $p['ahab']);
        $this->spouse($tree, $p['ahab'], $p['jezebel']);
        $this->parentChild($tree, $p['ethbaal'], $p['jezebel']);
        foreach (['ahaziah_israel', 'joram_israel', 'athaliah'] as $child) {
            $this->parentChild($tree, $p['ahab'], $p[$child]);
            $this->parentChild($tree, $p['jezebel'], $p[$child]);
        }

        // Judah line: Jehoshaphat → Jehoram ↔ Athaliah → Ahaziah of Judah
        $this->parentChild($tree, $p['jehoshaphat'], $p['jehoram_judah']);
        $this->spouse($tree, $p['jehoram_judah'], $p['athaliah']);
        $this->parentChild($tree, $p['jehoram_judah'], $p['ahaziah_judah']);
        $this->parentChild($tree, $p['athaliah'], $p['ahaziah_judah']);

        // Ahaziah of Judah ↔ Zibiah → Joash of Judah
        $this->spouse($tree, $p['ahaziah_judah'], $p['zibiah']);
        $this->parentChild($tree, $p['ahaziah_judah'], $p['joash_judah']);
        $this->parentChild($tree, $p['zibiah'], $p['joash_judah']);

        // Davidic chain to Jesus
        $davidicChain = [
            'joash_judah', 'amaziah', 'uzziah', 'jotham', 'ahaz',
            'hezekiah', 'manasseh', 'amon', 'josiah',
        ];
        for ($i = 0; $i < count($davidicChain) - 1; $i++) {
            $this->parentChild($tree, $p[$davidicChain[$i]], $p[$davidicChain[$i + 1]]);
        }

        // Josiah's sons (Jehoahaz not in Matthew's line; Jehoiakim → Jehoiachin is)
        $this->parentChild($tree, $p['josiah'], $p['jehoahaz_judah']);
        $this->parentChild($tree, $p['josiah'], $p['jehoiakim']);
        $this->parentChild($tree, $p['jehoiakim'], $p['jehoiachin']);

        // Post-exile chain
        $postExile = [
            'jehoiachin', 'shealtiel', 'zerubbabel',
            'abiud', 'eliakim_mt', 'azor', 'zadok_mt', 'achim',
            'eliud', 'eleazar_mt', 'matthan', 'jacob_mt', 'joseph',
        ];
        for ($i = 0; $i < count($postExile) - 1; $i++) {
            $this->parentChild($tree, $p[$postExile[$i]], $p[$postExile[$i + 1]]);
        }

        // Joseph ↔ Mary → Jesus
        $this->spouse($tree, $p['joseph'], $p['mary']);
        $this->parentChild($tree, $p['joseph'], $p['jesus']);
        $this->parentChild($tree, $p['mary'], $p['jesus']);

        // Jehu's dynasty (parallel northern line)
        $this->parentChild($tree, $p['nimshi'], $p['jehoshaphat_nimshi']);
        $this->parentChild($tree, $p['jehoshaphat_nimshi'], $p['jehu']);
        $jehuChain = ['jehu', 'jehoahaz', 'jehoash_israel', 'jeroboam_ii', 'zechariah_israel'];
        for ($i = 0; $i < count($jehuChain) - 1; $i++) {
            $this->parentChild($tree, $p[$jehuChain[$i]], $p[$jehuChain[$i + 1]]);
        }
    }

    private function parentChild(FamilyTree $tree, Person $parent, Person $child): void
    {
        PersonRelationship::firstOrCreate(
            ['person_id' => $parent->id, 'related_person_id' => $child->id, 'type' => 'parent'],
            ['family_tree_id' => $tree->id]
        );
        PersonRelationship::firstOrCreate(
            ['person_id' => $child->id, 'related_person_id' => $parent->id, 'type' => 'child'],
            ['family_tree_id' => $tree->id]
        );
    }

    private function spouse(FamilyTree $tree, Person $a, Person $b): void
    {
        PersonRelationship::firstOrCreate(
            ['person_id' => $a->id, 'related_person_id' => $b->id, 'type' => 'spouse'],
            ['family_tree_id' => $tree->id]
        );
        PersonRelationship::firstOrCreate(
            ['person_id' => $b->id, 'related_person_id' => $a->id, 'type' => 'spouse'],
            ['family_tree_id' => $tree->id]
        );
    }
}
