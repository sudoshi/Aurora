export interface MmeMatch {
  id: number;
  odyssey_id: number;
  direction: string;
  peer_id: number | null;
  score: number;
  matched_label: string | null;
  matched_contact_name: string | null;
  matched_contact_href: string | null;
  status: string;
  created_at: string;
}
