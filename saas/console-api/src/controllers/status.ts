import { Request, Response } from 'express';

export const status = (req: Request, res: Response) => {
  res.status(200).send();
};
