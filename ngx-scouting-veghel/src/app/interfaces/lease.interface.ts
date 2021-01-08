export enum LeasedBy {
  ScoutingVeghel,
  Dorshout,
  Extern,
}

export interface SvLeasedBase {
  startUtc: Date | string;
  endUtc: Date | string;
  name: string;
  leasedBy: LeasedBy;
}
